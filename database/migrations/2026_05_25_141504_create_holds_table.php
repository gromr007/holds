<?php

use App\Enums\Hold\HoldEnum;
use App\Models\Slot;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('holds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('slot_id')
                ->comment('Связь со слотом')
                ->constrained('slots')
                ->cascadeOnDelete();

            $table->unsignedTinyInteger('status')->comment('Статус: 1=held, 2=confirmed, 3=cancelled');

            // ДОБАВЛЯЕМ КЛЮЧ ИДЕМПОТЕНТНОСТИ
            $table->uuid('idempotency_key')->comment('Уникальный ключ операции для защиты от дублей');

            /**
             * Добавляем уникальность, для магкого удаления
             */
            $table->string('idempotency_active_sign')->default('ACTIVE');
            $table->unique(['idempotency_key', 'idempotency_active_sign'], 'holds_idempotency_unique');

            $table->timestamps();
            $table->softDeletes();

            // Индекс для ускорения фильтрации по статусам внутри слота
            $table->index(['slot_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('holds');
    }
};
