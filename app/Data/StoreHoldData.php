<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\Validation\Uuid;

class StoreHoldData extends Data
{
    public function __construct(
        #[Uuid]
        public string $idempotencyKey,

        public int $slotId,
    ) {}
}

