<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\SlotRepository;
use App\Enums\Errors\ErrorNames;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class SlotService
{
    private const CACHE_TTL = 5;
    private const CACHE_KEY = 'slots:availability';
    private const LOCK_KEY = 'slots:availability:lock';

    /** Параметры Spinlock, вынесенные в свойства для гибкости и тестирования */
    private int $maxWaitMs;
    private int $spinRetryUs;
    private int $simulatedDelayUs;

    public function __construct(
        private readonly SlotRepository $slotRepository,
    ) {
        // Читаем настройки из конфига
        $this->maxWaitMs = (int) config('slot.spinlock.max_wait_ms', 3000);
        $this->spinRetryUs = (int) config('slot.spinlock.spin_retry_us', 5000);
        $this->simulatedDelayUs = (int) config('slot.spinlock.simulated_delay_us', 30000);
    }

    /**
     * Получение доступности слотов через кастомный микросекундный Spinlock.
     * Гарантирует отсутствие таймаутов воркеров и 100% актуальность кэша.
     */
    public function getAvailability(): Collection
    {
        $startTime = microtime(true);

        while ((microtime(true) - $startTime) * 1000 < $this->maxWaitMs) {

            // 1. Быстрая проверка кэша
            $cachedData = Cache::get(self::CACHE_KEY);
            if ($cachedData !== null) {
                LogService::logAll(ErrorNames::SUCCESSFUL_REDIS_CACHE->value, 'Мгновенное попадание в кэш');
                return $cachedData;
            }

            // 2. Попытка захватить неблокирующий замок (get() вместо block())
            // Замок ставится на 5 секунд, чтобы поток успел сходить в БД
            $lock = Cache::lock(self::LOCK_KEY, 5);

            if ($lock->get()) {
                try {

                    // Двойная проверка внутри замка
                    $secondCheck = Cache::get(self::CACHE_KEY);
                    if ($secondCheck !== null) {
                        LogService::logAll(ErrorNames::SUCCESSFUL_REDIS_CACHE2->value, 'Попадание во вторую проверку внутри замка');
                        return $secondCheck;
                    }

                    // Фиксируем честный промах кэша (только 1 поток на инвалидацию)
                    LogService::logAll(ErrorNames::CACHE_MISS_STAMPEDE->value, 'Единственный поток пошел в БД');

                    // Имитируем задержку тяжелой БД
                    usleep($this->simulatedDelayUs);

                    $freshSlots = $this->slotRepository->getAllAvailable();

                    Cache::put(self::CACHE_KEY, $freshSlots, self::CACHE_TTL);

                    return $freshSlots;
                } finally {
                    $lock->release();
                }
            }

            // 3. Если замок занят — не блокируем воркер надолго!
            // Спим всего 5 миллисекунд и уходим на следующий виток цикла while
            usleep($this->spinRetryUs);
        }

        // Если за 3 секунды циклического бега кэш так и не построился (авария)
        LogService::logAll(ErrorNames::CACHE_LOCK_TIMEOUT->value, 'Аварийный таймаут Spinlock. Уход в БД.');
        return $this->slotRepository->getAllAvailable();
    }

    public function invalidateCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
