<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Data\StoreHoldData;
use App\Models\Hold;
use App\Enums\Hold\HoldStatus;
use Illuminate\Support\Facades\DB;

class HoldRepository
{
    /**
     * Создать запись холда.
     */
    public function createHold(int $slotId, StoreHoldData $dto): Hold
    {
        return Hold::create([
            'slot_id' => $slotId,
            'status' => HoldStatus::HELD->value,
            'idempotency_key' => $dto->idempotencyKey,
        ]);
    }

    /**
     * Найти активный холд внутри конкретного слота.
     */
    public function findHoldInSlot(int $slotId, int $holdId): ?Hold
    {
        return Hold::where('id', $holdId)
            ->where('slot_id', $slotId)
            ->first();
    }

    /**
     * Найти дубликат ключа идемпотентности.
     */
    public function findHoldDubble(string $key): int
    {
        return DB::table('holds')->where('idempotency_key', $key)->count();
    }

    /**
     * @param int $id
     * @return bool
     */
    public function existsById(int $id): bool
    {
        return Hold::query()->where('id', $id)->exists();
    }

}
