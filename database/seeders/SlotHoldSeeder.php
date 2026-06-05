<?php

namespace Database\Seeders;

use App\Enums\Errors\ErrorNames;
use App\Models\Slot;
use App\Models\Hold;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SlotHoldSeeder extends Seeder
{
    /**
     * Создаем Слоты и Холды к ним
     */
    public function run(): void
    {
        // 1. Принудительно обнуляем метрики в Pushgateway перед тестом
        Artisan::call('metrics:reset');

        Slot::insert([
            [
                'id' => 1, //Слот для нагрузочного тестирования и демонстрации
                'capacity' => 100,
                'remaining' => 100,
            ],
            [
                'id' => 2,
                'capacity' => 100,
                'remaining' => 100,
            ],
            [
                'id' => 3,
                'capacity' => 5,
                'remaining' => 0,
            ],
            [ //Слот для нагрузочного тестирования и демонстрации
                'id' => 4,
                'capacity' => 3,
                'remaining' => 1,
            ],
        ]);

        $slotsDefault = Slot::get()->toArray();
        $slots = Slot::factory()->count(10)->create()->toArray();
        foreach (array_merge($slotsDefault, $slots) as $slot) {
            $countHeldConfirmed = $slot['capacity'] - $slot['remaining'];
            Hold::factory()->count($countHeldConfirmed)->create([
                'slot_id' => $slot['id']
            ]);
            $countCanceledRandom = random_int(1, 5);
            Hold::factory()->cancelled()->count($countCanceledRandom)->create([
                'slot_id' => $slot['id']
            ]);
        }

        //Слот для мониторинга работоспособности
        DB::table('slots')->insert([
            'id' => 100,
            'capacity' => 2,
            'remaining' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Перед запуском тестов создаем 50 холдов с фиксированными ID для Слота №1
        for ($i = 1000; $i < 1050; $i++) {
            DB::table('holds')->insert([
                'id' => $i,
                'slot_id' => 1,
                'status' => 1, // held
                'idempotency_key' => Str::uuid()->toString(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        DB::table('slots')->where('id',1)->update(['remaining' => 50 ]);

    }
}
