<?php

namespace App\Services\Interfaces;

interface IAffordabilityService
{
    public function calculateAffordability(float $creditScore): array;

    public function getAffordabilityCategories(float $creditScore);
}
