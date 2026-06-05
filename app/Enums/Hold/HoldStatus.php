<?php

declare(strict_types=1);

namespace App\Enums\Hold;

/**
 * Ошибки
 */
enum HoldStatus: int
{
    /** Временное удержание слота */
    case HELD = 1;

    /** Бронь успешно подтверждена пользователем */
    case CONFIRMED = 2;

    /** Бронь отменена, остаток возвращен в слот */
    case CANCELLED = 3;

    /** Временное удержание слота */
    public const HELD_LABEL = 'held';

    /** Бронь успешно подтверждена пользователем */
    public const CONFIRMED_LABEL = 'confirmed';

    /** Бронь отменена, остаток возвращен в слот */
    public const CANCELLED_LABEL = 'cancelled';

    /** Дефолтный возврат */
    public const DEFAULT_LABEL = 'unknown';


    /**
     * Получить строковое представление статуса
     */
    public static function getLabelByStatus(int $statusId): string
    {
        $status = self::tryFrom($statusId);

        $map = [
            self::HELD->value      => self::HELD_LABEL,
            self::CONFIRMED->value => self::CONFIRMED_LABEL,
            self::CANCELLED->value => self::CANCELLED_LABEL,
        ];

        return $map[$status?->value] ?? self::DEFAULT_LABEL;
    }
}
