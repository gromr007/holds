<?php

declare(strict_types=1);

namespace App\Enums\Errors;

/**
 * Ошибки
 */
enum ErrorNames: string
{
    /** Ошибка оверсейла */
    case SUCCESSFUL_OVERSELL = 'SUCCESSFUL_OVERSELL';

    /** Слот переполнен */
    case SLOT_OVERSOLD = 'SLOT_OVERSOLD';
    case SLOT_OVERSOLD2 = 'SLOT_OVERSOLD2';

    /** Холд не найден */
    case HOLD_NOT_FOUND = 'HOLD_NOT_FOUND';
    case HOLD_NOT_FOUND_CONFIRM = 'HOLD_NOT_FOUND_CONFIRM';
    case HOLD_NOT_FOUND_CANCEL = 'HOLD_NOT_FOUND_CANCEL';

    /** Ключ идемпотентности уже в работе */
    case IDEMPOTENCY_KEY_REQUIRED = 'IDEMPOTENCY_KEY_REQUIRED';

    /** Успешное бронирование */
    case SUCCESSFUL_BOOKING = 'SUCCESSFUL_BOOKING';

    /** Количество одновременных походов в бд при сбросе кеша */
    case CACHE_MISS_STAMPEDE = 'CACHE_MISS_STAMPEDE';

    /** Данные успешно взяты из кеша */
    case SUCCESSFUL_REDIS_CACHE = 'SUCCESSFUL_REDIS_CACHE';
    case SUCCESSFUL_REDIS_CACHE2 = 'SUCCESSFUL_REDIS_CACHE2';
    case SUCCESSFUL_REDIS_CACHE3 = 'SUCCESSFUL_REDIS_CACHE3';

    /** Неизвестная ошибка сервера */
    case UNKNOWN_SERVER_ERROR = 'UNKNOWN_SERVER_ERROR';

    /** Если замок не дождался ответа по таймауту, аварийно отдаем данные напрямую из БД */
    case CACHE_LOCK_TIMEOUT = 'CACHE_LOCK_TIMEOUT';

    /** Ошибка - Дублирование ключа идемпотентности */
    case IDEMPOTENCY_KEY_DABBLE = 'IDEMPOTENCY_KEY_DABBLE';
    case IDEMPOTENCY_KEY_DABBLE2 = 'IDEMPOTENCY_KEY_DABBLE2';

    /** Холд подтвержден! */
    case SUCCESSFUL_CONFIRM = 'SUCCESSFUL_CONFIRM';

    /** Холд отменен! */
    case SUCCESSFUL_CANCEL = 'SUCCESSFUL_CANCEL';
    case SUCCESSFUL_CANCEL1 = 'SUCCESSFUL_CANCEL1';
    case SUCCESSFUL_CANCEL2 = 'SUCCESSFUL_CANCEL2';

    /**
     * Замена старого метода getErrors().
     * Возвращает массив чистых строк (если это необходимо для обратной совместимости).
     *
     * @return string[]
     */
    public static function getValues(): array
    {
        return array_column(self::cases(), 'value');
    }
}
