<?php

namespace Tests\Feature;

use App\Models\Slot;
use App\Repositories\SlotRepository;
use App\Services\SlotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class SlotAvailabilityCacheTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
		
		// Переводим очереди в режим "притворства" (fake).
		Queue::fake();


        // Полностью очищаем Redis перед каждым тестом
        Cache::flush();
    }

    /**
     * ТЕСТ 1: Защита от Cache Stampede.
     * Проверяем, что при одновременных запросах к пустому кэшу,
     * физический запрос в БД выполнится СТРОГО ОДИН РАЗ.
     */
    public function test_spinlock_prevents_cache_stampede_by_calling_db_only_once(): void
    {
        // 1. ДАНО: Создаем тестовые слоты в БД
        Slot::factory()->count(3)->create();

        // Создаем шпиона (Mock) для SlotRepository, чтобы посчитать точное количество вызовов БД
        $spyRepository = $this->createMock(SlotRepository::class);

        // Мы ожидаем, что метод getAllAvailable() вызовется СТРОГО ОДИН РАЗ (exactly(1))
        $spyRepository->expects($this->exactly(1))
            ->method('getAllAvailable')
            ->willReturn(Slot::all());

        // Пересоздаем SlotService, внедрив в него нашего шпиона вместо реального репозитория
        $slotService = new SlotService($spyRepository);

        // 2. ДЕЙСТВИЕ: Имитируем "набег" (Stampede) из 3 последовательных вызовов.
        // Первый поток зайдет, увидит пустой кэш, захватит замок (Lock) и пойдет в БД (вызов №1).
        $result1 = $slotService->getAvailability();

        // Второй и третий потоки вызываются следом. Кэш уже прогрет первым потоком.
        // Благодаря Spinlock и Double-Check, они заберут данные из Redis, НЕ дергая БД.
        $result2 = $slotService->getAvailability();
        $result3 = $slotService->getAvailability();

        // 3. ПРОВЕРКА: Данные во всех трех случаях вернулись корректные
        $this->assertCount(3, $result1);
        $this->assertCount(3, $result2);
        $this->assertCount(3, $result3);

        // Если бы защита не сработала, Mock выбросил бы ошибку:
        // "Expectation failed: method getAllAvailable() was expected to be called 1 time, but was called 3 times."
    }

    /**
     * ТЕСТ 2: Проверка аварийного режима (Fallback).
     * Если замок удерживается вечно (авария), сервис должен отдать данные напрямую из БД по таймауту.
     */
    public function test_spinlock_returns_db_data_directly_on_lock_timeout(): void
    {
        Slot::factory()->count(2)->create();

        // Искусственно вешаем замок намертво в Redis, имитируя зависший параллельный поток
        Cache::lock('slots:availability:lock', 5)->get();

        $slotService = app(SlotService::class);

        // Используем Reflection для изменения PRIVATE свойств класса
        $reflection = new \ReflectionClass($slotService);

        // Находим свойство maxWaitMs и принудительно ставим 5 миллисекунд вместо 3000
        $maxWaitMs = $reflection->getProperty('maxWaitMs');
        $maxWaitMs->setAccessible(true);
        $maxWaitMs->setValue($slotService, 5);

        // Находим свойство spinRetryUs (шаг цикла) и ставим 1 миллисекунду (1000 микросекунд)
        $spinRetryUs = $reflection->getProperty('spinRetryUs');
        $spinRetryUs->setAccessible(true);
        $spinRetryUs->setValue($slotService, 1000);

        // ДЕЙСТВИЕ: Запрашиваем доступность. Сервер упрется в занятый замок,
        // побегает по циклу while всего 5 мс, выйдет по таймауту и пойдет напрямую в БД.
        $result = $slotService->getAvailability();

        // ПРОВЕРКА: Данные успешно получены в обход замка
        $this->assertCount(2, $result);
    }
}
