<?php

namespace App\Generators;

use App\Values\DrawResult;

interface MatchGenerator
{
    /**
     * @param  list<int>  $participantIds  Ordered participant IDs.
     * @return DrawResult Deterministic schedule from the given order.
     */
    public function generate(array $participantIds): DrawResult;

    public function formatLabel(): string;
}
