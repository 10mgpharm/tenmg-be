<?php

namespace App\Services;

use App\Services\Interfaces\IRuleEngineService;
use Illuminate\Database\Eloquent\Collection;

class RuleEngineService implements IRuleEngineService
{
    public function evaluate(array $transactions, Collection $loans, Collection $repayments): array
    {
        // Initialize the result variables by category
        $creditPattern = [
            'totalPastCreditCount' => count($loans),  // Total count of loans collected so far
            'totalPastCreditAmount' => 0,  // Sum of all loan amounts
            'activeCreditCount' => 0,  // Number of current active loans
            'activeCreditAmount' => 0,  // Sum of active loans
            'noOf10MGCredits' => 0,  // Number of credits fulfilled by 10MG
            'noOfExternalCredits' => 0,  // Number of credits fulfilled by external vendors
            'amountOf10MGCredits' => 0,  // Sum amount of credits fulfilled by 10MG
            'amountOfExternalCredits' => 0,  // Sum amount of credits fulfilled by external vendors
            'noOfAllRepayments' => count($repayments),  // Count of all repayments
            'noOfPartialRepayments' => 0,  // Partial repayments
            'noOfFullRepayments' => 0,  // Full repayments
            'noOfScheduleRepayments' => 0,  // Scheduled repayments
            'noOfLateRepayments' => 0,  // Late repayments
        ];

        // Analyze loans
        foreach ($loans as $loan) {
            $creditPattern['totalPastCreditAmount'] += $loan['total_amount'];

            if ($loan['status'] === 'ONGOING_REPAYMENT') {
                $creditPattern['activeCreditCount']++;
                $creditPattern['activeCreditAmount'] += $loan['total_amount'];
            }

            // Check if the loan was fulfilled by 10MG or external vendors
            if ($loan['vendor'] === '10MG') {
                $creditPattern['noOf10MGCredits']++;
                $creditPattern['amountOf10MGCredits'] += $loan['total_amount'];
            } else {
                $creditPattern['noOfExternalCredits']++;
                $creditPattern['amountOfExternalCredits'] += $loan['total_amount'];
            }
        }

        // Analyze repayments
        foreach ($repayments as $repayment) {
            if ($repayment['payment_status'] === 'PARTIAL') {
                $creditPattern['noOfPartialRepayments']++;
            } elseif ($repayment['payment_status'] === 'FULL') {
                $creditPattern['noOfFullRepayments']++;
            }

            if ($repayment['late_fee'] > 0) {
                $creditPattern['noOfLateRepayments']++;
            }
        }

        // Initialize the purchase pattern result
        $purchasePattern = [
            'totalTransactionVolume' => 0,
            'averageTransactionVolume' => 0,
            'totalTransactionCount' => count($transactions),
            'noOfTransactingMonths' => 0,
            'listOfTransactingMonths' => [],
            'noOfOmmitedMonths' => 0,
            'highestTransactingMonth' => [],
            'lowestTransactingMonth' => [],
        ];

        // Track monthly totals
        $monthlyTotals = [];

        foreach ($transactions as $transaction) {
            $purchasePattern['totalTransactionVolume'] += $transaction['Amount'];
            $transactionDate = \Carbon\Carbon::parse($transaction['Date']);
            $monthYear = $transactionDate->format('F Y');

            // Aggregate transactions by month
            if (! isset($monthlyTotals[$monthYear])) {
                $monthlyTotals[$monthYear] = 0;
            }
            $monthlyTotals[$monthYear] += $transaction['Amount'];

            if (! in_array($monthYear, $purchasePattern['listOfTransactingMonths'])) {
                $purchasePattern['listOfTransactingMonths'][] = $monthYear;
            }
        }

        // Set transacting months
        $purchasePattern['noOfTransactingMonths'] = count($purchasePattern['listOfTransactingMonths']);

        // Assuming evaluation spans at least 6 months, calculate omitted months
        $purchasePattern['noOfOmmitedMonths'] = max(0, 6 - $purchasePattern['noOfTransactingMonths']);

        // Calculate highest and lowest transacting month
        $purchasePattern['highestTransactingMonth'] = collect($monthlyTotals)->sortDesc()->first();
        $purchasePattern['lowestTransactingMonth'] = collect($monthlyTotals)->sort()->first();

        // Calculate the average transaction volume
        if ($purchasePattern['noOfTransactingMonths'] > 0) {
            $purchasePattern['averageTransactionVolume'] = $purchasePattern['totalTransactionVolume'] / $purchasePattern['noOfTransactingMonths'];
        }

        return [
            'creditPattern' => $creditPattern,
            'purchasePattern' => $purchasePattern,
        ];
    }

    public function applyRules(array $evaluationResult, array $activeRules): array
    {
        $score = 0;
        $totalScore = 0;
        $appliedRules = [];

        foreach ($activeRules as $rule) {
            // Determine the value from the evaluation result that corresponds to the rule

            $comparisonValue = $evaluationResult['creditPattern'][$rule['name']] ?? $evaluationResult['purchasePattern'][$rule['name']] ?? 0;

            // Apply the rule based on the condition and logical operator
            if ($this->evaluateCondition($rule['logical_operator'], $comparisonValue, $rule['compare_value'])) {
                $score += $rule['score_weight'];
                $appliedRules[] = [
                    'rule_name' => $rule['name'],
                    'rule_description' => $rule['description'],
                    'status' => 'passed',
                    'weight' => $rule['score_weight'],
                    'transaction_value' => $comparisonValue,
                    'operator' => $rule['logical_operator'],
                    'system_value' => $rule['compare_value'],

                ];
            } else {
                $appliedRules[] = [
                    'rule_name' => $rule['name'],
                    'rule_description' => $rule['description'],
                    'status' => 'failed',
                    'weight' => 0,
                    'transaction_value' => $comparisonValue,
                    'operator' => $rule['logical_operator'],
                    'system_value' => $rule['compare_value'],
                ];
            }

            // Sum up the total possible score
            $totalScore += $rule['score_weight'];
        }

        $scorePercent = ($score / $totalScore) * 100;

        return [
            'applied_rules' => $appliedRules,
            'score_percent' => $scorePercent,
            'score_value' => $score,
            'score_total' => $totalScore,
        ];
    }

    public function evaluateCondition($operator, $comparisonValue, $ruleValue): bool
    {
        switch ($operator) {
            case '>':
                return $comparisonValue > $ruleValue;
            case '<':
                return $comparisonValue < $ruleValue;
            case '>=':
                return $comparisonValue >= $ruleValue;
            case '<=':
                return $comparisonValue <= $ruleValue;
            case '==':
                return $comparisonValue == $ruleValue;
            case '!=':
                return $comparisonValue != $ruleValue;
            default:
                return false;
        }
    }
}
