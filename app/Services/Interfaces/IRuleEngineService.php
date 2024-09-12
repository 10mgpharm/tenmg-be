<?php

namespace App\Services\Interfaces;

use Illuminate\Database\Eloquent\Collection;

interface IRuleEngineService
{
    public function evaluate(array $transactions, Collection $loans, Collection $repayments): array;

    public function applyRules(array $evaluationResult, array $activeRules): array;

    public function evaluateCondition($operator, $comparisonValue, $ruleValue): bool;
}
