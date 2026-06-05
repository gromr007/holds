<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SlotService;
use App\Http\Resources\SlotResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SlotController extends Controller
{
    public function __construct(
        private readonly SlotService $slotService
    ) {
    }

    /**
     * Получить список доступных слотов.
     */
    public function availability(): AnonymousResourceCollection
    {
        $slots = $this->slotService->getAvailability();

        return SlotResource::collection($slots);
    }
}

