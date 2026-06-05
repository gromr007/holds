<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Data\ShowDeleteHoldData;
use App\Data\StoreHoldData;
use App\Http\Requests\AbstractRequest;
use App\Http\Requests\CustomRules\HoldIdExists;
use App\Http\Requests\CustomRules\SlotIdExists;
use Illuminate\Support\Facades\Log;

final class ShowDeleteHoldRequest extends AbstractRequest
{
    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            'slotId' => [
                'bail',
                'required',
                'integer',
                'min:1',
                app(SlotIdExists::class),
            ],
            'holdId' => [
                'bail',
                'required',
                'integer',
                'min:1',
                app(HoldIdExists::class),
            ]
        ];
    }

    /**
     * Подмешиваем данные в массив валидации
     * */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'slotId' => (int)$this->route('slot_id'),
            'holdId' => (int)$this->route('hold_id'),
        ]);
    }

    /**
     * @return ShowDeleteHoldData
     */
    public function toData(): ShowDeleteHoldData
    {
        return ShowDeleteHoldData::from($this->validated());
    }

}
