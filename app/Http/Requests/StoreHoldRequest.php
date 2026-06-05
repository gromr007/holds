<?php

namespace App\Http\Requests;

use App\Data\StoreHoldData;
use App\Exceptions\IdempotencyKeyException;
use App\Http\Requests\CustomRules\SlotIdExists;
use App\Models\Slot;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Support\Facades\Log;

class StoreHoldRequest extends AbstractRequest
{

    public function rules(): array
    {
        return [
            'idempotencyKey' => [
                'bail',
                'required',
                'string',
                'uuid',
            ],
            'slotId' => [
                'bail',
                'required',
                'integer',
                'min:1',
                app(SlotIdExists::class),
            ],
        ];
    }

    /**
     * Подмешиваем данные в массив валидации
     * */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'idempotencyKey' => $this->header('Idempotency-Key'),
            'slotId' => (int)$this->route('slot_id'),
        ]);
    }


    /**
     * @return StoreHoldData
     */
    public function toData(): StoreHoldData
    {
        return StoreHoldData::from($this->validated());
    }

}
