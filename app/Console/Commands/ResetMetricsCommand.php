<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\Errors\ErrorNames;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ResetMetricsCommand extends Command
{
    /** Имя команды для вызова в терминале */
    protected $signature = 'metrics:reset';

    /** Описание команды */
    protected $description = 'Принудительно обнуляет бизнес-метрики в Prometheus Pushgateway перед стартом системы или тестов';

    public function handle(): void
    {
        $errorCodes = ErrorNames::getValues();

        foreach ($errorCodes as $code) {
            try {
                $payload = "# HELP laravel_booking_errors_total Total number of booking business exceptions.\n" .
                    "# TYPE laravel_booking_errors_total counter\n" .
                    "laravel_booking_errors_total{error_code=\"{$code}\"} 0\n";

                $pushgatewayUrl = "http://vds_pushgateway:9091/metrics/job/laravel_api_{$code}";

                // Гарантированно перезаписываем старые метрики методом PUT
                Http::timeout(2)->withBody($payload, 'text/plain')->put($pushgatewayUrl);
            } catch (\Exception $e) {
                Log::warning("Could not reset metric for code {$code}: " . $e->getMessage());
            }
        }

        $this->info('Все бизнес-метрики в Pushgateway успешно инициализированы нулевыми значениями.');
    }
}
