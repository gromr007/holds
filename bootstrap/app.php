<?php

use App\Enums\Errors\ErrorNames;
use App\Exceptions\BookingException;
use App\Models\ApplicationLog;
use App\Services\LogService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {

        /**
         * Глобальный перехват кастомных бизнес-исключений.
         * Записывает ошибку в БД для mysql-exporter и отдает структурированный JSON.
         */
        $exceptions->render(function (\Throwable $exception, Request $request) {

            // 1. Определяем строковый код ошибки
            if ($exception instanceof BookingException) {
                // Если это наше кастомное исключение (например, оверсел или не найден холд)
                $errorCode = $exception->getErrorCode();
                $statusCode = $exception->getStatusCode();
                $message = $exception->getMessage();
            } elseif ($exception instanceof QueryException && str_contains($exception->getMessage(), 'out of range')) {
                // Если это системный взрыв MySQL из-за наивного декремента в минус unsigned поля
                $errorCode = ErrorNames::SLOT_OVERSOLD2->value; // Для бизнеса это чистый оверсел
                $statusCode = 409;
                $message = 'Database numeric out of range: slot capacity breached.';
            } else {
                $errorCode = ErrorNames::UNKNOWN_SERVER_ERROR->value;
                $statusCode = 400; // Переводим в контролируемый 4хх статус
                $message = 'An unexpected error occurred on the server. Please try again later.';
            }

            // Контекст для внутренних логов (Сюда мы сохраняем ВСЮ правду для дебага)
            $context = [
                'url'             => $request->fullUrl(),
                'method'          => $request->method(),
                'exception_class' => get_class($exception),
                'real_message'    => $exception->getMessage(), // Настоящая ошибка пишется ТОЛЬКО в логи
                'trace'           => substr($exception->getTraceAsString(), 0, 1000), // Первые 1000 символов трейса
            ];

            // --- ФИКСАЦИЯ В МОНИТОРИНГЕ --- Данные берутся из БД
            LogService::logAll($errorCode, $message, $context);

            // 5. Возвращаем клиенту красивый JSON вместо стандартного HTML-взрыва Laravel
            return response()->json([
                'success'    => false,
                'error_code' => $errorCode,
                'message'    => $message
            ], $statusCode);

        });

    })->create();
