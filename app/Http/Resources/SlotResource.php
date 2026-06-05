<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read int $id
 * @property-read int $capacity
 * @property-read int $remaining
 */
class SlotResource extends JsonResource
{
    /**
     * Преобразование модели слота в массив для ответа API.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'slot_id'   => $this->id,
            'capacity'  => $this->capacity,
            'remaining' => $this->remaining,
        ];
    }
}
