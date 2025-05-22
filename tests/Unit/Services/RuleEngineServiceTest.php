<?php

namespace Tests\Unit\Services;

use App\Services\RuleEngineService;
use Illuminate\Database\Eloquent\Collection;

beforeEach(function () {
    $this->ruleEngineService = new RuleEngineService;
});

it('evaluates transactions, loans, and repayments correctly', function () {
    $transactions = [
        ['Amount' => 5000, 'Date' => '2023-01-01'],
        ['Amount' => 3000, 'Date' => '2023-02-01'],
    ];

    $loans = new Collection([
        ['total_amount' => 10000, 'status' => 'ONGOING_REPAYMENT', 'vendor' => '10MG'],
        ['total_amount' => 5000, 'status' => 'CLOSED', 'vendor' => 'External'],
    ]);

    $repayments = new Collection([
        ['payment_status' => 'FULL', 'late_fee' => 0],
        ['payment_status' => 'PARTIAL', 'late_fee' => 10],
    ]);

    $result = $this->ruleEngineService->evaluate($transactions, $loans, $repayments);

    expect($result['creditPattern']['totalPastCreditCount'])->toBe(2);
    expect($result['creditPattern']['totalPastCreditAmount'])->toBe(15000);
    expect($result['purchasePattern']['totalTransactionVolume'])->toBe(8000);
});

it('applies rules to evaluation result and returns correct credit score', function () {
    $evaluationResult = [
        'creditPattern' => ['totalPastCreditAmount' => 15000],
        'purchasePattern' => ['totalTransactionVolume' => 8000],
    ];

    $activeRules = [
        ['name' => 'totalPastCreditAmount', 'logical_operator' => '>=', 'compare_value' => 10000, 'score_weight' => 50, 'description' => ''],
        ['name' => 'totalTransactionVolume', 'logical_operator' => '>=', 'compare_value' => 5000, 'score_weight' => 50, 'description' => ''],
    ];

    $result = $this->ruleEngineService->applyRules($evaluationResult, $activeRules);

    expect($result['score_percent'])->toBe(100);
    expect($result['applied_rules'][0]['status'])->toBe('passed');
    expect($result['applied_rules'][1]['status'])->toBe('passed');
});
