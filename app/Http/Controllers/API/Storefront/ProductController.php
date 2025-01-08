<?php

namespace App\Http\Controllers\API\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Resources\Storefront\EcommerceProductResource;
use App\Models\EcommerceProduct;
use App\Services\Admin\Storefront\EcommerceProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    // Inject the EcommerceProductService to handle product logic
    public function __construct(private EcommerceProductService $productService) {}

    /**
     * Retrieve details of a single product.
     *
     * @param  \Illuminate\Http\Request  $request  Request instance.
     * @param  EcommerceProduct  $product  Product to show.
     * @return \Illuminate\Http\JsonResponse JSON response with product details.
     */
    public function show(Request $request, EcommerceProduct $product): JsonResponse
    {
        $product = $this->productService->show($request, $product);

        return $this->returnJsonResponse(
            message: 'Product successfully fetched.',
            data: $product
        );
    }

    /**
     * Retrieve a paginated list of products based on the search criteria.
     *
     * @param  \Illuminate\Http\Request  $request  Request instance.
     * @return \Illuminate\Http\JsonResponse JSON response with paginated product data.
     */
    public function search(Request $request): JsonResponse
    {
        $products = $this->productService->search($request);

        return $this->returnJsonResponse(
            message: 'Products successfully fetched.',
            data: $products
        );
    }
}
