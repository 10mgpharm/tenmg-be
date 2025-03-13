<?php

namespace App\Http\Controllers\API\Supplier;

use App\Enums\ProductInsightsFilterEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Supplier\ProductInsightsRequest;
use App\Services\Supplier\ProductInsightsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductInsightsController extends Controller
{

    public function __construct(ProductInsightsService $productInsightsService){}

    /**
     * Handle product insights retrieval.
     *
     * @param ProductInsightsRequest $request The incoming request.
     * @param ProductInsightsService $productInsightsService The service handling product insights.
     * @return JsonResponse The response containing product insights.
     */
    public function insights(ProductInsightsRequest $request, ProductInsightsService $productInsightsService): JsonResponse
    {
        $validated = $request->validated();
        $user = $request->user();

        $insights = $productInsightsService->insights($validated, $user);

        return $this->returnJsonResponse(
            message: "Product insights successfully fetched.",
            data: $insights,
        );
    }

    public function filters(Request $request): JsonResponse
    {

        return $this->returnJsonResponse(
            message: "Product insights successfully fetched.",
            data: array_map(fn($case) => strtolower($case->name), ProductInsightsFilterEnum::cases()),
        );
    }
}
