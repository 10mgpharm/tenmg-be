<?php

namespace App\Repositories;

use App\Models\Affordability;
use Illuminate\Database\Eloquent\Collection;

class AffordabilityRepository
{
    public function getDefaultRule(): ?Affordability
    {
        return Affordability::where('is_default', true)->first();
    }

    public function getActiveRules(): Collection
    {
        return Affordability::where('active', true)->get();
    }

    public function getRuleByCreditScorePercent(float $creditScorePercent): ?Affordability
    {
        return Affordability::where('active', true)
            ->where('lower_bound', '<=', $creditScorePercent)
            ->where('upper_bound', '>', $creditScorePercent)
            ->first();
    }
}
