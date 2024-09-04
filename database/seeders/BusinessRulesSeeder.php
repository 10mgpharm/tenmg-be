<?php

namespace Database\Seeders;

use App\Models\CreditBusinessRule;
use App\Models\CreditEvaluationCategory;
use Illuminate\Database\Seeder;

class BusinessRulesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $evaluationCategories = [
            'Credit Pattern',
            'Purchase Pattern',
        ];

        // Create or retrieve evaluation categories
        $evaluationRecords = [];
        foreach ($evaluationCategories as $categoryName) {
            $evaluationRecords[$categoryName] = CreditEvaluationCategory::firstOrCreate(
                ['name' => $categoryName],
                ['code' => $categoryName],
                ['description' => $categoryName]
            );
        }

        // Define the group of business rules associated with each category
        $group = [
            'Purchase Pattern' => [
                ['name' => 'totalTransactionVolume', 'description' => 'Total sum amount of all purchases done'],
                ['name' => 'averageTransactionVolume', 'description' => 'Average sum amount of all purchases done i.e totalTransactionVolume / noOfTransactingMonths'],
                ['name' => 'totalTransactionCount', 'description' => 'Total count of all purchases done'],
                ['name' => 'noOfTransactingMonths', 'description' => 'Total count of how many months in the txn history e.g 6 i.e 6 Months'],
                ['name' => 'listOfTransactingMonths', 'description' => 'Array list of all months identified in the transaction [“June, 2024”, “July 2024”, “August 2024”,......]'],
                ['name' => 'noOfOmmitedMonths', 'description' => 'Months customer did not perform any transaction'],
                ['name' => 'highestTransactingMonth', 'description' => 'Object of month name and year, amount purchase'],
                ['name' => 'lowestTransactingMonth', 'description' => 'Object of month name and year, amount purchase'],
            ],
            'Credit Pattern' => [
                ['name' => 'totalPastCreditCount', 'description' => 'The total count of loans collected so far'],
                ['name' => 'totalPastCreditAmount', 'description' => 'The total sum amount of loans collected so far'],
                ['name' => 'activeCreditCount', 'description' => 'Number of current active loans if exist'],
                ['name' => 'activeCreditAmount', 'description' => 'Sum of current active loans if exist'],
                ['name' => 'noOf10MGCredits', 'description' => 'Number of credits fulfilled by 10MG'],
                ['name' => 'noOfExternalCredits', 'description' => 'Number of credits fulfilled by VENDOR'],
                ['name' => 'amountOf10MGCredits', 'description' => 'Sum amount of credits fulfilled by 10MG'],
                ['name' => 'amountOfExternalCredits', 'description' => 'Sum amount of credits fulfilled by VENDOR'],
                ['name' => 'noOfAllRepayments', 'description' => 'Count of all repayments performed by customer'],
                ['name' => 'noOfPartialRepayments', 'description' => 'Count of all partial repayments performed by customer'],
                ['name' => 'noOfFullRepayments', 'description' => 'Count of all full repayments performed by customer'],
                ['name' => 'noOfScheduleRepayments', 'description' => 'Count of schedule repayments performed by customer'],
                ['name' => 'noOfLateRepayments', 'description' => 'Count of all late repayments performed by customer'],
            ],
        ];

        // Insert business rules into the database
        foreach ($group as $categoryName => $rules) {
            foreach ($rules as $rule) {
                CreditBusinessRule::firstOrCreate(
                    ['name' => $rule['name']],
                    [
                        'description' => $rule['description'],
                        'category_id' => $evaluationRecords[$categoryName]->id,
                    ]
                );
            }
        }

        // Insert constraints for business rules
        $constraints = [
            // Active rules
            ['name' => 'noOfLateRepayments', 'condition' => 'LessThan', 'logical_operator' => '<', 'compare_value' => 5, 'active' => true],
            ['name' => 'totalTransactionVolume', 'condition' => 'GreaterThanOrEqual', 'logical_operator' => '>=', 'compare_value' => 5000000, 'active' => true],
            ['name' => 'averageTransactionVolume', 'condition' => 'GreaterThanOrEqual', 'logical_operator' => '>=', 'compare_value' => 500000, 'active' => true],
            ['name' => 'totalTransactionCount', 'condition' => 'GreaterThan', 'logical_operator' => '>', 'compare_value' => 100, 'active' => true],
            ['name' => 'noOfTransactingMonths', 'condition' => 'GreaterThanOrEqual', 'logical_operator' => '>=', 'compare_value' => 6, 'active' => true],
            ['name' => 'noOfOmmitedMonths', 'condition' => 'Equals', 'logical_operator' => '==', 'compare_value' => 0, 'active' => true],
            ['name' => 'highestTransactionInMonth', 'condition' => 'GreaterThanOrEqual', 'logical_operator' => '>=', 'compare_value' => 900000, 'active' => true],
            ['name' => 'lowestTransactionInMonth', 'condition' => 'GreaterThanOrEqual', 'logical_operator' => '>=', 'compare_value' => 300000, 'active' => true],
            // Inactive rules
            ['name' => 'totalPastCreditCount', 'condition' => null, 'logical_operator' => null, 'compare_value' => null, 'active' => false],
            ['name' => 'totalPastCreditAmount', 'condition' => null, 'logical_operator' => null, 'compare_value' => null, 'active' => false],
            ['name' => 'activeCreditCount', 'condition' => null, 'logical_operator' => null, 'compare_value' => null, 'active' => false],
            ['name' => 'activeCreditAmount', 'condition' => null, 'logical_operator' => null, 'compare_value' => null, 'active' => false],
            ['name' => 'noOf10MGCredits', 'condition' => null, 'logical_operator' => null, 'compare_value' => null, 'active' => false],
            ['name' => 'noOfExternalCredits', 'condition' => null, 'logical_operator' => null, 'compare_value' => null, 'active' => false],
            ['name' => 'amountOf10MGCredits', 'condition' => null, 'logical_operator' => null, 'compare_value' => null, 'active' => false],
            ['name' => 'amountOfExternalCredits', 'condition' => null, 'logical_operator' => null, 'compare_value' => null, 'active' => false],
            ['name' => 'noOfAllRepayments', 'condition' => null, 'logical_operator' => null, 'compare_value' => null, 'active' => false],
            ['name' => 'noOfPartialRepayments', 'condition' => null, 'logical_operator' => null, 'compare_value' => null, 'active' => false],
            ['name' => 'noOfFullRepayments', 'condition' => null, 'logical_operator' => null, 'compare_value' => null, 'active' => false],
            ['name' => 'noOfScheduleRepayments', 'condition' => null, 'logical_operator' => null, 'compare_value' => null, 'active' => false],
        ];

        // Update business rules with constraints
        foreach ($constraints as $constraint) {
            CreditBusinessRule::where('name', $constraint['name'])
                ->update([
                    'condition' => $constraint['condition'],
                    'logical_operator' => $constraint['logical_operator'],
                    'compare_value' => $constraint['compare_value'],
                    'active' => $constraint['active'],
                ]);
        }
    }
}
