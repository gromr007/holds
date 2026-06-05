<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Модель для таблицы holds
 *
 * @property int         $id                Уникальный идентификатор записи
 * @property int         $status            Статус холда ['held', 'confirmed', 'cancelled']
 * @property string      $idempotency_key   Уникальный ключ идемпотентности
 * @property int         $slot_id           Связь один ко многим с Slot
 * @property string      $created_at        Метка времени.
 * @property string      $updated_at        Метка времени.
 * @property string      $deleted_at        Метка времени софт удаления.
 */
class Hold extends Model
{
    use HasFactory;
    use SoftDeletes;

    /*
    |--------------------------------------------------------------------------
    | GLOBAL VARIABLES
    |--------------------------------------------------------------------------
    */

    /** @var string */
    protected $table = 'holds';

    /** @var string[] */
    protected $guarded = ['id'];

    /** @var string */
    protected $primaryKey = 'id';

    /** @var string[] */
    protected $fillable = ['slot_id', 'status', 'idempotency_key'];

    /*
    |--------------------------------------------------------------------------
    | FUNCTIONS
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    /**
     * Обратная Связь один ко многим с таблицей slots
     * @return BelongsTo
     */
    public function slot(): BelongsTo
    {
        return $this->belongsTo(
            related: Slot::class,
            foreignKey: 'slot_id',
            ownerKey: 'id',
        );
    }


    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | MUTATORS
    |--------------------------------------------------------------------------
    */


}
