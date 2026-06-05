<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ShowDeleteHoldRequest;
use App\Http\Requests\StoreHoldRequest;
use App\Http\Requests\UpdateHoldRequest;
use App\Models\Hold;
use App\Services\HoldService;
use App\Http\Resources\HoldResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class HoldController extends Controller
{
    public function __construct(
        private readonly HoldService $holdService
    ) {}


    /**
     * Получить холд по id
     */
    public function show(ShowDeleteHoldRequest $request): HoldResource
    {
        $dto = $request->toData();
        $hold = Hold::findOrFail($dto->holdId);
        return HoldResource::make($hold);
    }

    /**
     * Создать временный холд на слот.
     */
    public function hold(StoreHoldRequest $request): JsonResponse
    {
        $dto = $request->toData();
        $hold = $this->holdService->createHold($dto);

        return (new HoldResource($hold))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED); // 201
    }

    /**
     * Подтвердить существующий холд.
     */
    public function confirm(UpdateHoldRequest $request): HoldResource
    {
        $dto = $request->toData();
        $hold = $this->holdService->confirmHold($dto);

        return new HoldResource($hold);
    }

    /**
     * Отменить холд.
     */
    public function cancel(ShowDeleteHoldRequest $request): HoldResource
    {
        $dto = $request->toData();
        $hold = $this->holdService->cancelHold($dto);

        return new HoldResource($hold);
    }
}
