<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

abstract class AbstractRequest extends FormRequest
{
    /**
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @param Validator $validator
     * @return void
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json(
                data:  [
                    'success' => false,
                    'error_code' => 'VALIDATE_ERROR',
                    'message' => 'Ошибка валидаторов',
                    'validation_errors' => array_merge([], $validator->errors()->toArray()),
                ],
                status: 422
            )
        );
    }
}
