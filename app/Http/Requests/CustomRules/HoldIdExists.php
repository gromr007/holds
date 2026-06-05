<?php

declare(strict_types=1);

namespace App\Http\Requests\CustomRules;

use App\Repositories\HoldRepository;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final class HoldIdExists implements ValidationRule
{

    /**
     * @param HoldRepository $repository
     */
    public function __construct(
        private readonly HoldRepository $repository
    )
    {
        //
    }

    /**
     * Run the validation rule.
     *
     * @param string $attribute
     * @param mixed $value
     * @param Closure $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($this->repository->existsById(id: (int)$value) === false) {
            $fail(':attribute ('.$value.') отсутствует в БД.');
        }
    }
}
