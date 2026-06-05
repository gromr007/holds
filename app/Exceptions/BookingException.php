<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;
use Symfony\Component\Component\HttpFoundation\Response;

abstract class BookingException extends Exception
{
    /**
     * @param string $message Публичный текст ошибки
     * @param string $errorCode Внутренний строковый код для систем мониторинга (Grafana/Prometheus)
     * @param int $statusCode HTTP-статус ответа
     */
    public function __construct(
        string $message,
        protected string $errorCode,
        protected int $statusCode = Response::HTTP_CONFLICT
    ) {
        parent::__construct($message, $statusCode);
    }

    /**
     * Получить внутренний код ошибки.
     */
    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    /**
     * Получить HTTP-статус.
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}
