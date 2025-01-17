<?php

namespace App\Http\Controllers\API\Storefront;

use App\Enums\StatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Resources\Storefront\StorefrontResource;
use App\Models\EcommerceCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StorefrontController extends Controller
{
    /**
     * Retrieve the list of active categories along with the latest 15 products for each.
     *
     * This method fetches all active categories from the `EcommerceCategory` model,
     * and for each category, it retrieves the 15 most recent products associated with it.
     * The results are returned in a JSON response wrapped in a `StorefrontResource`.
     *
     * @param  \Illuminate\Http\Request  $request  The incoming request instance.
     * @return \Illuminate\Http\JsonResponse Returns a JSON response with the active categories and their products.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $categories = EcommerceCategory::with([
            'products' => fn($query) => $query->where('active', 1)
                ->whereIn('status', StatusEnum::actives())
                ->latest()
                ->limit(15)
        ])->where('active', 1)
            ->whereIn('status', StatusEnum::actives())
            ->paginate($request->has('perPage') ? $request->perPage : 10)
            ->withQueryString()
            ->through(fn(EcommerceCategory $item) => StorefrontResource::make($item));

        return $this->returnJsonResponse(
            message: 'Products fetched successfully.',
            data: $categories
        );
    }
}
