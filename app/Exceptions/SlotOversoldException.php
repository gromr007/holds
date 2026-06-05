<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Enums\Errors\ErrorNames;
use Symfony\Component\HttpFoundation\Response;

/**
 * Исключение выбрасывается при попытке занять слот, в котором кончились места.
 */
class SlotOversoldException extends BookingException
{
    public function __construct(string $message = 'No available places left in this slot.')
    {
        parent::__construct(
            message: $message,
            errorCode: ErrorNames::SLOT_OVERSOLD->value,
            statusCode: Response::HTTP_CONFLICT // 409
        );
    }
}
