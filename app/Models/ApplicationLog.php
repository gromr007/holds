<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Модель для логирования аномалий и ошибок под нагрузкой.
 *
 * @property int $id
 * @property string $error_code
 * @property string $message
 * @property array|null $context
 * @property string $created_at
 */
class ApplicationLog extends Model
{
    /** Отключаем стандартные timestamps, нам нужен только created_at */
    public $timestamps = false;

    protected $table = 'application_logs';
    protected $fillable = ['error_code', 'message', 'context'];
    protected $casts = ['context' => 'array'];
}
