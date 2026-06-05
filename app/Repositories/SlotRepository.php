<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Slot;
use Illuminate\Database\Eloquent\Collection;

class SlotRepository
{
    /**
     * Получить все слоты для вывода доступности.
     * Прямой запрос в БД без кеша.
     *
     * @return Collection<int, Slot>
     */
    public function getAllAvailable(): Collection
    {
        return Slot::all();
    }

    /**
     * Найти слот по ID.
     */
    public function findById(int $id): ?Slot
    {
        return Slot::find($id);
    }

    /**
     * @param int $id
     * @return bool
     */
    public function existsById(int $id): bool
    {
        return Slot::query()->where('id', $id)->exists();
    }
}
