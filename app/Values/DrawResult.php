<?php

namespace App\Values;

final readonly class DrawResult
{
    public function __construct(
        /** @var list<DrawSlot> */
        public array $slots,
    ) {}
}
