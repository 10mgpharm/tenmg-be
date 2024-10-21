<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ListEcommerceProductRequest;
use App\Http\Requests\Admin\StoreEcommerceProductRequest;
use App\Http\Requests\Admin\UpdateEcommerceProductRequest;
use App\Http\Resources\EcommerceProductResource;
use App\Models\EcommerceProduct;
use App\Services\Admin\EcommerceProductService;
use Illuminate\Http\JsonResponse;

class EcommerceProductController extends Controller
{
    public function __construct(private EcommerceProductService $productService) {}

    /**
     * Retrieve a paginated list of products for the authenticated user's business.
     *
     * @param  \App\Http\Requests\Admin\ListEcommerceProductRequest  $request  Validated request instance.
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(ListEcommerceProductRequest $request): JsonResponse
    {
        $products = EcommerceProduct::latest()->paginate();

        return $this->returnJsonResponse(
            message: 'Products successfully fetched.',
            data: EcommerceProductResource::collection($products)->response()->getData(true)
        );
    }


    /**
     * Store a new product.
     *
     * This method handles the creation of a new product. It calls the product service to
     * ensure category, brand, and medication type exist and creates them if necessary.
     *
     * @param StoreEcommerceProductRequest $request
     * @return JsonResponse
     */
    public function store(StoreEcommerceProductRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $user = $request->user();

        $product = $this->productService->store($validated, $user);

        if (! $product) {
            return $this->returnJsonResponse(
                message: 'Oops, can\'t create product at the moment. Please try again later.'
            );
        }

        return $this->returnJsonResponse(
            message: 'Product successfully created.',
            data: new EcommerceProductResource($product)
        );
    }

    /**
     * Update a product.
     *
     * This method updates an existing product. It ensures that category, brand,
     * and medication type exist and creates them if necessary.
     *
     * @param UpdateEcommerceProductRequest $request
     * @param EcommerceProduct $product
     * @return JsonResponse
     */
    public function update(UpdateEcommerceProductRequest $request, EcommerceProduct $product): JsonResponse
    {
        $validated = $request->validated();
        $user = $request->user();

        $isUpdated = $this->productService->update($validated, $user, $product);

        if (! $isUpdated) {
            return $this->returnJsonResponse(
                message: 'Oops, can\'t update product at the moment. Please try again later.'
            );
        }

        return $this->returnJsonResponse(
            message: 'Product successfully updated.',
            data: new EcommerceProductResource($product->refresh())
        );
    }
}
