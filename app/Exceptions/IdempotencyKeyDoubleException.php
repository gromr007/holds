<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Enums\Errors\ErrorNames;
use Symfony\Component\HttpFoundation\Response;

/**
 * Исключение выбрасывается, если в заголовках отсутствует или передан некорректный Idempotency-Key.
 */
class IdempotencyKeyDoubleException extends BookingException
{
    public function __construct(string $message = 'Idempotency key required or invalid format.')
    {
        parent::__construct(
            message: $message,
            errorCode: ErrorNames::IDEMPOTENCY_KEY_DABBLE2->value,
            statusCode: Response::HTTP_BAD_REQUEST // 400
        );
    }
}
