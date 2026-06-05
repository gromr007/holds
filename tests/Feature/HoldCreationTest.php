<?php

namespace Tests\Feature;

use App\Models\Slot;
use App\Models\Hold;
use App\Jobs\PushMetricsJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

class HoldCreationTest extends TestCase
{
    // Этот трейт автоматически очищает тестовую БД перед каждым тестом
    // и накатывает миграции заново. База всегда будет чистой!
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Переводим очереди в режим "притворства" (fake).
        // Перехватывает стандартный менеджер очередей и подменяет его на "фальшивый" класс QueueFake.
        // Теперь вместо реального выполнения джобы будут просто записываться в память теста.
        Queue::fake();

        // Полностью очищаем тестовую базу Redis перед каждым тестом
        Cache::flush();
    }

    /**
     * ТЕСТ 1: Успешный сценарий
     */
    public function test_can_create_hold_successfully(): void
    {
        // 1. ДАНО: Создаем в тестовой базе слот, где есть 5 свободных мест
        $slot = Slot::factory()->create([
            'capacity' => 5,
            'remaining' => 5,
        ]);
        $idempotencyKey = Str::uuid()->toString();

        // 2. ДЕЙСТВИЕ: Делаем POST запрос к нашему API, передавая заголовок идемпотентности
        $response = $this->withHeaders([
            'Idempotency-Key' => $idempotencyKey,
        ])->postJson("/api/slots/{$slot->id}/holds");

        // 3. ПРОВЕРКА:
        // Клиент должен получить HTTP 201 (Created)
        $response->assertStatus(201);

        // Проверяем структуру ответа (как в HoldResource)
        $response->assertJsonPath('data.status', 'held');
        $response->assertJsonPath('data.slot_id', $slot->id);

        // Проверяем, что в БД остаток места уменьшился с 5 до 4
        // SELECT COUNT(*) FROM `slots` WHERE `id` = 1 AND `remaining` = 4;
        $this->assertDatabaseHas('slots', [
            'id' => $slot->id,
            'remaining' => 4,
        ]);

        // Проверяем, что в БД физически создалась запись холда
        $this->assertDatabaseHas('holds', [
            'slot_id' => $slot->id,
            'idempotency_key' => $idempotencyKey,
            'status' => 1, // held
        ]);

        // Проверяем, что в Redis создался кэш идемпотентности
        $this->assertTrue(Cache::has("idempotency:key:{$idempotencyKey}"));

        // Проверяем, в памяти теста, что наша фоновая задача на отправку метрик была отправлена в очередь,
        Queue::assertPushed(PushMetricsJob::class);
    }

    /**
     * ТЕСТ 2: Бизнес-отказ (Оверсел)
     */
    public function test_cannot_create_hold_if_slot_is_full(): void
    {
        // 1. ДАНО: Мест нет (remaining = 0)
        $slot = Slot::factory()->create([
            'capacity' => 5,
            'remaining' => 0,
        ]);

        // 2. ДЕЙСТВИЕ: Пытаемся занять место
        $response = $this->withHeaders([
            'Idempotency-Key' => Str::uuid()->toString(),
        ])->postJson("/api/slots/{$slot->id}/holds");

        // 3. ПРОВЕРКА:
        // Наш bootstrap/app.php должен перехватить ошибку и отдать контролируемый 409 конфликт
        $response->assertStatus(409);
        $response->assertJson([
            'success' => false,
            'error_code' => 'SLOT_OVERSOLD',
        ]);

        // Проверяем, что в базу удержаний ничего не записалось
        $this->assertEquals(0, Hold::count());
    }

    /**
     * ТЕСТ 3: Проверка жесткой идемпотентности (Защита от дублей)
     */
    public function test_idempotency_returns_cached_hold_on_duplicate_request(): void
    {
        // 1. ДАНО: Слот с местами
        $slot = Slot::factory()->create(['capacity' => 10, 'remaining' => 10]);
        $idempotencyKey = Str::uuid()->toString();

        // 2. ДЕЙСТВИЕ: Делаем первый запрос
        $firstResponse = $this->withHeaders(['Idempotency-Key' => $idempotencyKey])
            ->postJson("/api/slots/{$slot->id}/holds");
        $firstResponse->assertStatus(201);

        $firstHoldId = $firstResponse->json('data.hold_id');

        // Отправляем ТОЧНО ТАКОЙ ЖЕ запрос второй раз (имитируем сбой сети у клиента)
        $secondResponse = $this->withHeaders(['Idempotency-Key' => $idempotencyKey])
            ->postJson("/api/slots/{$slot->id}/holds");

        // 3. ПРОВЕРКА:
        $secondResponse->assertStatus(201);
        // Должен вернуться тот же самый ID холда
        $this->assertEquals($firstHoldId, $secondResponse->json('data.hold_id'));

        // САМОЕ ГЛАВНОЕ: Место в слоте должно было списаться ТОЛЬКО ОДИН РАЗ (остаток 9, а не 8)
        $this->assertDatabaseHas('slots', [
            'id' => $slot->id,
            'remaining' => 9,
        ]);
    }

    /**
     * ТЕСТ 4: Успешное подтверждение холда
     */
    public function test_can_confirm_hold_successfully(): void
    {
        // 1. ДАНО: Создаем слот и холд в статусе 1 (held)
        $slot = Slot::factory()->create(['capacity' => 5, 'remaining' => 4]);
        $hold = Hold::factory()->create([
            'slot_id' => $slot->id,
            'status' => 1, // held
            'idempotency_key' => Str::uuid()->toString(),
        ]);

        // Положим ключ в кэш, как будто он там был после создания
        Cache::put("idempotency:key:{$hold->idempotency_key}", $hold, 86400);

        // 2. ДЕЙСТВИЕ: Вызываем PATCH-эндпоинт подтверждения
        $response = $this->patchJson("/api/slots/{$slot->id}/holds/{$hold->id}");

        // 3. ПРОВЕРКА:
        $response->assertStatus(200);
        $response->assertJsonPath('data.status', 'confirmed');

        // Проверяем, что в БД статус стал 2 (confirmed)
        $this->assertDatabaseHas('holds', [
            'id' => $hold->id,
            'status' => 2,
        ]);

        // Проверяем, что старый кэш идемпотентности стерся (из логики HoldService)
        $this->assertFalse(Cache::has("idempotency:key:{$hold->idempotency_key}"));
    }

    /**
     * ТЕСТ 5: Ошибка валидации, если холд УЖЕ удален/отменен
     */
    public function test_cannot_confirm_hold_if_it_is_already_cancelled(): void
    {
        // 1. ДАНО: Холд уже софт-удален
        $slot = Slot::factory()->create();
        $hold = Hold::factory()->create([
            'slot_id' => $slot->id,
            'status' => 3, // cancelled
            'deleted_at' => now(),
        ]);

        // 2. ДЕЙСТВИЕ: Пытаемся подтвердить его
        $response = $this->patchJson("/api/slots/{$slot->id}/holds/{$hold->id}");

        // 3. ПРОВЕРКА: Валидатор HoldIdExists не увидит софт-удаленную запись и вернет 422
        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'error_code' => 'VALIDATE_ERROR',
        ]);
    }

    /**
     * ТЕСТ 6: Успешная отмена холда после создания и возврат места в слот
     */
    public function test_can_cancel_hold_after_held_successfully_and_restore_slot_remaining(): void
    {
        // 1. ДАНО: Слот, в котором осталось 4 места из 5 (одно место держит холд)
        $slot = Slot::factory()->create(['capacity' => 5, 'remaining' => 4]);
        $hold = Hold::factory()->create([
            'slot_id' => $slot->id,
            'status' => 1, // held
            'idempotency_key' => Str::uuid()->toString(),
        ]);

        // 2. ДЕЙСТВИЕ: Вызываем DELETE-эндпоинт отмены
        $response = $this->deleteJson("/api/slots/{$slot->id}/holds/{$hold->id}");

        // 3. ПРОВЕРКА:
        $response->assertStatus(200);
        $response->assertJsonPath('data.status', 'cancelled');

        // Проверяем Soft Delete: запись помечена удаленной
        $this->assertSoftDeleted('holds', [
            'id' => $hold->id,
            'status' => 3, // cancelled
        ]);

        // Проверяем высвобождение составного уникального индекса (idempotency_active_sign стал UUID-знаком)
        $updatedHold = Hold::withTrashed()->find($hold->id);
        $this->assertNotEquals('ACTIVE', $updatedHold->idempotency_active_sign);

        // Место вернулось в слот! (Остаток вырос с 4 до 5)
        $this->assertDatabaseHas('slots', [
            'id' => $slot->id,
            'remaining' => 5,
        ]);
    }

    /**
     * ТЕСТ 7: Успешная отмена холда после подтверждения и возврат места в слот
     */
    public function test_can_cancel_hold_after_confirm_successfully_and_restore_slot_remaining(): void
    {
        // 1. ДАНО: Слот, в котором осталось 4 места из 5 (одно место держит холд)
        $slot = Slot::factory()->create(['capacity' => 5, 'remaining' => 4]);
        $hold = Hold::factory()->create([
            'slot_id' => $slot->id,
            'status' => 2, // confirm
            'idempotency_key' => Str::uuid()->toString(),
        ]);

        // 2. ДЕЙСТВИЕ: Вызываем DELETE-эндпоинт отмены
        $response = $this->deleteJson("/api/slots/{$slot->id}/holds/{$hold->id}");

        // 3. ПРОВЕРКА:
        $response->assertStatus(200);
        $response->assertJsonPath('data.status', 'cancelled');

        // Проверяем Soft Delete: запись помечена удаленной
        $this->assertSoftDeleted('holds', [
            'id' => $hold->id,
            'status' => 3, // cancelled
        ]);

        // Проверяем высвобождение составного уникального индекса (idempotency_active_sign стал UUID-знаком)
        $updatedHold = Hold::withTrashed()->find($hold->id);
        $this->assertNotEquals('ACTIVE', $updatedHold->idempotency_active_sign);

        // Место вернулось в слот! (Остаток вырос с 4 до 5)
        $this->assertDatabaseHas('slots', [
            'id' => $slot->id,
            'remaining' => 5,
        ]);
    }


}
