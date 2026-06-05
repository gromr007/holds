<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;


/**
 * Модель для таблицы slots
 *
 * @property int         $id                Уникальный идентификатор записи
 * @property int         $capacity          Емкость Слота
 * @property int         $remaining         Остаток Слота.
 * @property string      $created_at        Метка времени.
 * @property string      $updated_at        Метка времени.
 * @property string      $deleted_at        Метка времени софт удаления.
 *
 * @property Collection<int, Hold> $holds
 */
class Slot extends Model
{
    use HasFactory;
    use SoftDeletes;


    /*
    |--------------------------------------------------------------------------
    | GLOBAL VARIABLES
    |--------------------------------------------------------------------------
    */

    /** @var string */
    protected $table = 'slots';

    /** @var string[] */
    protected $guarded = ['id'];

    /** @var string */
    protected $primaryKey = 'id';


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
     * Связь один ко многим с таблицей holds
     * @return HasMany
     */
    public function holds(): HasMany
    {
        return $this->hasMany(
            related: Hold::class,
            foreignKey: 'slot_id',
            localKey: 'id',
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
