<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Enums\Hold\HoldStatus;

/**
 * @property-read int $id
 * @property-read int $slot_id
 * @property-read int $status
 */
class HoldResource extends JsonResource
{
    /**
     * Преобразование модели холда в понятный фронтенду JSON.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'hold_id' => $this->id,
            'slot_id' => $this->slot_id,
            'status'  => HoldStatus::getLabelByStatus($this->status),
        ];
    }
}
