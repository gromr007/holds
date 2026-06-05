<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\ShowDeleteHoldData;
use App\Data\StoreHoldData;
use App\Data\UpdateHoldData;
use App\Enums\Errors\ErrorNames;
use App\Exceptions\HoldNotFoundCancelException;
use App\Exceptions\HoldNotFoundConfirmException;
use App\Exceptions\IdempotencyKeyDoubleException;
use App\Http\Requests\ShowDeleteHoldRequest;
use App\Models\ApplicationLog;
use App\Models\Slot;
use App\Repositories\SlotRepository;
use App\Repositories\HoldRepository;
use App\Exceptions\SlotOversoldException;
use App\Exceptions\HoldNotFoundException;
use Illuminate\Database\QueryException;
use App\Models\Hold;
use App\Enums\Hold\HoldStatus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class HoldService
{

    /** Время искусственной задержки для симуляции тяжелых проверок (в микросекундах) */
    private const SIMULATED_MICROSERVICE_DELAY = 30000; // 30 миллисекунд

    /** Время жизни закэшированного ответа идемпотентности в секундах (24 часа) */
    private const IDEMPOTENCY_TTL = 86400;
    private const IDEMPOTENCY_PREFIX = 'idempotency:key:';

    public function __construct(
        private readonly SlotRepository $slotRepository,
        private readonly HoldRepository $holdRepository,
        private readonly SlotService $slotService,
    ) {}

    public function createHold(StoreHoldData $dto): Hold
    {
        $cacheKey = self::IDEMPOTENCY_PREFIX . $dto->idempotencyKey;
        $slotId = $dto->slotId;

        // ШАГ 1: Быстрая проверка в Redis (без изменений)
        $cachedHold = Cache::get($cacheKey);
        if ($cachedHold !== null) {
            LogService::logAll(ErrorNames::SUCCESSFUL_REDIS_CACHE->value, 'Идемпотентный ответ успешно отдан из Redis');
            return $cachedHold;
        }

        try {
            // Если тут упадет дубликат — транзакция гарантированно сделает ROLLBACK и вернет остаток слота!
            return DB::transaction(function () use ($slotId, $dto, $cacheKey) {

                // Атомарный декремент остатка
                $affectedRows = Slot::where('id', $slotId)
                    ->where('remaining', '>', 0)
                    ->decrement('remaining', 1);

                if ($affectedRows === 0) {
                    throw new SlotOversoldException('Все места в слоте заняты.');
                }

                // Физически создаем запись холда
                $hold = $this->holdRepository->createHold($slotId, $dto);

                // Инвалидируем кэш доступности слотов
                DB::afterCommit(function () use ($cacheKey, $hold) {
                    $this->slotService->invalidateCache();
                    Cache::put($cacheKey, $hold, self::IDEMPOTENCY_TTL);
                });

                LogService::logAll(ErrorNames::SUCCESSFUL_BOOKING->value, 'Создан новый уникальный холд');

                return $hold;
            });

        } catch (QueryException $e) {

            // ШАГ 3: ОБРАБОТКА ДУБЛИКАТА СНАРУЖИ (Когда транзакция УЖЕ откатилась в СУБД)
            if ($e->errorInfo[0] === "23000" || str_contains($e->getMessage(), 'Duplicate entry') || $e->getCode() == '23000') {

                // Находим тот самый первый холд, который записался на наносекунду раньше
                $existingHold = Hold::where('idempotency_key', $dto->idempotencyKey)->first();

                if ($existingHold) {
                    // пишем его в Redis
                    Cache::put($cacheKey, $existingHold, self::IDEMPOTENCY_TTL);
                    return $existingHold;
                }

                throw new IdempotencyKeyDoubleException('Такой ключ идемпотентности уже существует!');

            } else {
                // Если это какая-то другая непредвиденная ошибка БД — пробрасываем её в bootstrap/app.php
                throw $e;
            }
        }
    }


    /**
     * Подтверждение холда с уязвимостью к гонке статусов.
     *
     * @throws HoldNotFoundException
     */
    public function confirmHold(UpdateHoldData $dto): Hold
    {
        $slotId = $dto->slotId;
        $holdId = $dto->holdId;

        // Выполняем атомарное обновление статуса прямо в СУБД с проверкой старого статуса.
        // Запрос вернет количество обновленных строк (affected rows).
        $affectedRows = Hold::where('id', $holdId)
            ->where('slot_id', $slotId)
            ->where('status', 1) // Ищем строго в статусе 1 (held)
            ->whereNull('deleted_at') // Исключаем софт-удаленные дубли
            ->update([
                'status' => 2, // Переводим в 2 (confirmed)
                'updated_at' => now(),
            ]);

        /**
         * КУПИРОВАНИЕ ГОНКИ: Если affectedRows === 0, значит, в эту же миллисекунду
         * параллельный поток отмены УЖЕ успел изменить статус на 3 или удалить запись.
         * Мы перехватываем этот момент и отдаем бизнес-отказ!
         */
        if ($affectedRows === 0) {
            throw new HoldNotFoundConfirmException('Невозможно подтвердить текущий холд');
        }

        // Симулируем задержку на внешние операции (теперь она абсолютно безопасна!)
        usleep(self::SIMULATED_MICROSERVICE_DELAY);

        $hold = Hold::findOrFail($holdId);

        // Стираем старый кэш идемпотентности в Redis!
        Cache::forget(self::IDEMPOTENCY_PREFIX . $hold->idempotency_key);

        LogService::logAll(ErrorNames::SUCCESSFUL_CONFIRM->value, "Hold #{$holdId} successfully confirmed via Optimistic Lock.");

        // Возвращаем актуальную модель для Resource-ответа
        return $hold;

    }

    /**
     * Отмена холда с гарантированной Оптимистичной Блокировкой (Optimistic Locking).
     *
     * @throws HoldNotFoundException
     */
    public function cancelHold(ShowDeleteHoldData $dto): Hold
    {
        $slotId = $dto->slotId;
        $holdId = $dto->holdId;

        // Оборачиваем отмену в транзакцию, чтобы декремент статуса холда
        // и инкремент остатка слота выполнились как единое атомарное действие.
        return DB::transaction(function () use ($slotId, $holdId) {

            // ГЕНЕРИРУЕМ УНИКАЛЬНЫЙ ЗНАК УДАЛЕНИЯ
            $deletionCancellationUuid = Str::uuid()->toString();


            // Атомарно меняем статус на 3 (cancelled), только если он был равен 1 (held)
            $affectedRows1 = Hold::where('id', $holdId)
                ->where('slot_id', $slotId)
                ->where('status', 1)
                ->whereNull('deleted_at')
                ->update([
                    'status' => 3, // 3 = cancelled
                    'deleted_at' => now(), // Делаем soft-delete вручную в один запрос!
                    'idempotency_active_sign' => $deletionCancellationUuid, // Записываем UUID
                    'updated_at' => now(),
                ]);

            // Атомарно меняем статус на 3 (cancelled), только если он был равен 2
            $affectedRows2 = Hold::where('id', $holdId)
                ->where('slot_id', $slotId)
                ->where('status', 2)
                ->whereNull('deleted_at')
                ->update([
                    'status' => 3, // 3 = cancelled
                    'idempotency_active_sign' => $deletionCancellationUuid, // Записываем UUID
                    'deleted_at' => now(), // Делаем soft-delete вручную в один запрос!
                    'updated_at' => now(),
                ]);

            // Если это статус 1 и поток подтверждения успел раньше поставить двойку, то будет 0
            // Если это статус 2, то проверяем что есть изменнные
            if ($affectedRows1 === 0 && $affectedRows2 === 0) {
                throw new HoldNotFoundCancelException('This hold cannot be cancelled because it is already confirmed.');
            }

            usleep(self::SIMULATED_MICROSERVICE_DELAY);

            // МЕСТО ВОЗВРАЩАЕТСЯ СТРОГО ПОСЛЕ УСПЕШНОГО ДЕКРЕМЕНТА ХОЛДА
            // Используем атомарный инкремент СУБД, чтобы избежать потери мест
            Slot::where('id', $slotId)->increment('remaining', 1);

            $hold = Hold::withTrashed()->findOrFail($holdId);
            $idempotencyKey = $hold->idempotency_key;

            // Инвалидируем кэш слотов СТРОГО после успешного коммита транзакции
            DB::afterCommit(function () use ($idempotencyKey) {
                $this->slotService->invalidateCache();
                // Также стираем кэш идемпотентности
                Cache::forget(self::IDEMPOTENCY_PREFIX . $idempotencyKey);
            });

            if($affectedRows1 !== 0) {
                LogService::logAll(
                    ErrorNames::SUCCESSFUL_CANCEL1->value,
                    "Hold #{$holdId} successfully cancelled via Optimistic Lock."
                );
            } elseif($affectedRows2 !== 0) {
                LogService::logAll(
                    ErrorNames::SUCCESSFUL_CANCEL2->value,
                    "Hold #{$holdId} successfully cancelled via Optimistic Lock."
                );
            }

            return $hold; //Hold::withTrashed()->findOrFail($holdId);
        });
    }

}
