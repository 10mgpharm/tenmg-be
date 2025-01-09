<?php

namespace App\Http\Controllers\API\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Resources\Storefront\EcommerceProductResource;
use App\Models\EcommerceCategory;
use App\Services\Admin\Storefront\EcommerceCategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function __construct(private EcommerceCategoryService $categoryService) {}

    /**
     * Retrieve a paginated list of products for a specific category.
     *
     * This method fetches all products associated with the provided category
     * and returns them in a paginated format, wrapped in a JSON response.
     *
     * @param  \Illuminate\Http\Request  $request  The incoming request instance containing query parameters.
     * @param  \App\Models\EcommerceCategory  $category  The category for which to fetch products.
     * @return \Illuminate\Http\JsonResponse Returns a JSON response with a paginated list of products.
     */
    public function products(Request $request, EcommerceCategory $category): JsonResponse
    {
        $products = $this->categoryService->products($request, $category);

        return $this->returnJsonResponse(
            message: 'Products successfully fetched.',
            data: [
                'category' => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                ],
                ...$products,
            ]
        );
    }

    /**
     * Search for products across categories based on given criteria.
     *
     * This method allows searching for products across all categories using various
     * filters like name, price, date range, and other relevant parameters.
     * The results are returned in a paginated JSON format.
     *
     * @param  \Illuminate\Http\Request  $request  The incoming request instance containing search parameters.
     * @return \Illuminate\Http\JsonResponse Returns a JSON response with a paginated list of search results.
     */
    public function search(Request $request): JsonResponse
    {
        $products = $this->categoryService->search($request);

        return $this->returnJsonResponse(
            message: 'Products successfully fetched.',
            data: $products
        );
    }
}
