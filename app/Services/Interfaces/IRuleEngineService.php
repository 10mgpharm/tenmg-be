<?php

namespace App\Services\Interfaces;

use Illuminate\Database\Eloquent\Collection;

interface IRuleEngineService
{
    public function evaluate(array $transactions, Collection $loans, Collection $repayments);

    public function applyRules(array $evaluationResult, array $activeRules);

    public function evaluateCondition($operator, $comparisonValue, $ruleValue);
}
