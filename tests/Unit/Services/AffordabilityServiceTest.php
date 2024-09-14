<?php

namespace Tests\Unit\Services;

use App\Models\Affordability;
use App\Repositories\AffordabilityRepository;
use App\Services\AffordabilityService;

beforeEach(function () {
    $this->affordabilityRepositoryMock = mock(AffordabilityRepository::class);
    $this->affordabilityService = new AffordabilityService($this->affordabilityRepositoryMock);
});

it('calculates affordability based on credit score', function () {
    $rule = new Affordability;
    $rule->lower_bound = 45;
    $rule->upper_bound = 60;
    $rule->base_amount = 100000;
    $rule->max_amount = 500000;
    $this->affordabilityRepositoryMock->shouldReceive('getRuleByCreditScorePercent')->once()->with(50)->andReturn($rule);

    $result = $this->affordabilityService->calculateAffordability(50);

    expect($result['base_amount'])->toBe(100000);
    expect($result['max_amount'])->toBe(500000);
});

it('applies default affordability rule if no rule is found', function () {
    $defaultRule = new Affordability;
    $defaultRule->base_amount = 50000;
    $defaultRule->max_amount = 100000;
    $this->affordabilityRepositoryMock->shouldReceive('getRuleByCreditScorePercent')->once()->with(40)->andReturn(null);
    $this->affordabilityRepositoryMock->shouldReceive('getDefaultRule')->once()->andReturn($defaultRule);

    $result = $this->affordabilityService->calculateAffordability(40);

    expect($result['base_amount'])->toBe(50000);
    expect($result['max_amount'])->toBe(100000);
});

it('throws an exception when no rules are found', function () {
    $this->affordabilityRepositoryMock->shouldReceive('getRuleByCreditScorePercent')->once()->with(40)->andReturn(null);
    $this->affordabilityRepositoryMock->shouldReceive('getDefaultRule')->once()->andReturn(null);

    expect(fn () => $this->affordabilityService->calculateAffordability(40))
        ->toThrow(\Exception::class, 'No applicable affordability rules found.');
});
