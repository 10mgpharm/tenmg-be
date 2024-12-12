<?php

namespace App\Services;

use App\Repositories\AffordabilityRepository;
use App\Services\Interfaces\IAffordabilityService;
use Exception;

class AffordabilityService implements IAffordabilityService
{
    public function __construct(
        private AffordabilityRepository $affordabilityRepository
    ) {}

    public function calculateAffordability(float $creditScore): array
    {

        // Step 1: check which rule applies to the credit score and fetch it
        $affordability = $this->affordabilityRepository->getRuleByCreditScorePercent($creditScore);
        if ($affordability) {
            return [
                'base_amount' => $affordability->base_amount,
                'max_amount' => $affordability->max_amount,
                'rule' => "Score falls between {$affordability->lower_bound}% and {$affordability->upper_bound}%",
                'category' => $affordability->category
            ];
        }

        // Step 2: If no rule matches, apply the default rule
        $defaultAffordability = $this->affordabilityRepository->getDefaultRule();
        if ($defaultAffordability) {
            return [
                'base_amount' => $defaultAffordability->base_amount,
                'max_amount' => $defaultAffordability->max_amount,
                'rule' => 'Default affordability rule applied',
                'category' => $defaultAffordability->category
            ];
        }

        // Step 3: If no rules are applicable, throw an error
        throw new Exception('No applicable affordability rules found.');
    }

    public function getAffordabilityCategories(float $creditScore)
    {
        // Step 1: check which rule applies to the credit score and fetch it
        $affordability = $this->affordabilityRepository->getRuleByCreditScorePercent($creditScore);
        if ($affordability) {
            return $affordability->category;
        }

        // Step 2: If no rule matches, apply the default rule
        $defaultAffordability = $this->affordabilityRepository->getDefaultRule();
        if ($defaultAffordability) {
            return $defaultAffordability->category;
        }

        // Step 3: If no rules are applicable, throw an error
        throw new Exception('No applicable affordability rules found.');
    }
}
