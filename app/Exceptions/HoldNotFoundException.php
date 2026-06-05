<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Enums\Errors\ErrorNames;
use Symfony\Component\HttpFoundation\Response;

/**
 * Исключение выбрасывается, если холд не найден или его статус не позволяет сделать операцию.
 */
class HoldNotFoundException extends BookingException
{
    public function __construct(string $message = 'Requested hold not found or invalid.')
    {
        parent::__construct(
            message: $message,
            errorCode: ErrorNames::HOLD_NOT_FOUND->value,
            statusCode: Response::HTTP_NOT_FOUND // 404
        );
    }
}
