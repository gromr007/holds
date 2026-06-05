<?php

namespace App\Http\Requests;

use App\Data\UpdateHoldData;
use App\Http\Requests\CustomRules\HoldIdExists;
use App\Http\Requests\CustomRules\SlotIdExists;

class UpdateHoldRequest extends AbstractRequest
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
     * @return UpdateHoldData
     */
    public function toData(): UpdateHoldData
    {
        return UpdateHoldData::from($this->validated());
    }
}
