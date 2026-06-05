<?php

declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PushMetricsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Количество попыток выполнить задачу при сбое сети */
    public int $tries = 3;

    /** Задержка перед повторной попыткой (в секундах) */
    public int $backoff = 2;

    public function __construct(
        private readonly string $errorCode,
        private readonly int $totalMisses
    ) {}

    public function handle(): void
    {
        try {
            $payload = "# HELP laravel_booking_errors_total Total number of booking business exceptions and performance anomalies.\n" .
                "# TYPE laravel_booking_errors_total counter\n" .
                "laravel_booking_errors_total{error_code=\"" . $this->errorCode . "\"} {$this->totalMisses}\n";

            $url = 'http://vds_pushgateway:9091/metrics/job/laravel_api_' . $this->errorCode;

            // Здесь таймаут можно сделать чуть больше (например, 3 секунды), так как мы в фоне
            Http::timeout(3)
                ->withBody($payload, 'text/plain')
                ->post($url);

        } catch (\Throwable $e) {
            Log::error("Failed to push metric to Pushgateway in background: " . $e->getMessage());

            // Выбрасываем исключение, чтобы Laravel зафиксировал сбой и попробовал позже (до 3 раз)
            throw $e;
        }
    }
}
