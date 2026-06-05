<?php

declare(strict_types=1);

namespace App\Services;

use App\Jobs\PushMetricsJob;
use App\Models\ApplicationLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LogService
{
    public function __construct(

    ) {}

    /**
     *
     */
    public static function logAll(string $errorCode, string $message, array $context=[]): void
    {
        // 1. Записываем в БД
        $log = ApplicationLog::create([
            'error_code' => $errorCode,
            'message'    => $message,
            'context'    => $context,
        ]);

        // 2. Считаем сколько данных ошибок в бд к текущему моменту
        $totalMisses = ApplicationLog::where('error_code', $errorCode)->count();

        // 3. Отправляем метрику в Pushgateway
        PushMetricsJob::dispatch($errorCode, $totalMisses);

        // 4. В лог файл
        //Log::error($errorCode, [$message, $context]);

    }
}
