<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Создание таблицы для регистрации бизнес-ошибок.
     * Сюда mysql-exporter будет ходить за метриками для Grafana.
     */
    public function up(): void
    {
        Schema::create('application_logs', function (Blueprint $table) {
            $table->id();
            $table->string('error_code', 50)->comment('Строковый код ошибки, например, SLOT_OVERSOLD');
            $table->string('message')->comment('Текст ошибки для разработчиков');
            $table->json('context')->nullable()->comment('Дополнительный контекст (ID слота, параметры запроса)');
            $table->timestamp('created_at')->useCurrent()->index()->comment('Время фиксации сбоя');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_logs');
    }
};
