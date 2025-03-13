<?php

namespace App\Services\Interfaces;

use App\Http\Resources\Admin\ProductInsightsResource;
use App\Models\User;

interface IProductInsightsService
{
    /**
     * Get product insights based on the provided filter criteria.
     *
     * @param array $validated Validated filter data for insights retrieval.
     * @return ProductInsightsResource The product insights resource.
     */
    public function insights(array $validated, User $user): ProductInsightsResource;
}