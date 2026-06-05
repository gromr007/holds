<?php

namespace Database\Factories;

use App\Enums\Hold\HoldStatus;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Hold>
 */
class HoldFactory extends Factory
{
    /**
     * Общий метод вызываемый по умолчанию
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'status' => $this->faker->randomElement([HoldStatus::HELD->value, HoldStatus::CONFIRMED->value]),
            'idempotency_key' => $this->faker->uuid(),
        ];
    }

    /**
     * Состояние для отмененных перезаписывает данные в definition
     */
    public function cancelled()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => HoldStatus::CANCELLED->value,
                'idempotency_key' => $this->faker->uuid(),
                'deleted_at' => CarbonImmutable::now('UTC')->format('Y-m-d H:i:s'),
            ];
        });
    }

    /**
     * Состояние для зарезервированных перезаписывает данные в definition
     */
    public function held()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => HoldStatus::HELD->value,
                'idempotency_key' => $this->faker->uuid(),
            ];
        });
    }

    /**
     * Состояние для подтвержденных перезаписывает данные в definition
     */
    public function confirmed()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => HoldStatus::CONFIRMED->value,
                'idempotency_key' => $this->faker->uuid(),
            ];
        });
    }
}
