<?php

namespace App\Http\Controllers\API\Supplier;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supplier\DeleteEcommerceProductRequest;
use App\Http\Requests\Supplier\ListEcommerceProductRequest;
use App\Http\Requests\Supplier\ShowEcommerceProductRequest;
use App\Http\Requests\Supplier\StoreEcommerceProductRequest;
use App\Http\Requests\Supplier\UpdateEcommerceProductRequest;
use App\Http\Resources\EcommerceProductResource;
use App\Models\EcommerceProduct;
use App\Services\Admin\EcommerceProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EcommerceProductController extends Controller
{
    public function __construct(private EcommerceProductService $productService) {}

    /**
     * Retrieve a paginated list of products for the authenticated user's business.
     *
     * @param  \App\Http\Requests\Admin\ListEcommerceProductRequest  $request  Validated request instance.
     */
    public function index(ListEcommerceProductRequest $request): JsonResponse
    {
        $products = EcommerceProduct::latest()
        ->paginate($request->has('perPage') ? $request->perPage : 10)
        ->withQueryString()
        ->through(fn(EcommerceProduct $item) => EcommerceProductResource::make($item));

        return $this->returnJsonResponse(
            message: 'Products successfully fetched.',
            data: $products
        );
    }

    /**
     * Store a new product.
     *
     * This method handles the creation of a new product. It calls the product service to
     * ensure category, brand, and medication type exist and creates them if necessary.
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
     * Show an ecommerce product.
     *
     * @param ShowEcommerceProductRequest $request
     * @return JsonResponse
     */
    public function show(ShowEcommerceProductRequest $request, EcommerceProduct $product): JsonResponse
    {
        return $product
            ? $this->returnJsonResponse(
                message: 'Product successfully fetched.',
                data: new EcommerceProductResource($product)
            )
            : $this->returnJsonResponse(
                message: 'Oops, can\'t view product at the moment. Please try again later.'
            );
    }

    /**
     * Update a product.
     *
     * This method updates an existing product. It ensures that category, brand,
     * and medication type exist and creates them if necessary.
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

    /**
     * Retrieve a paginated list of products for the authenticated user's business.
     *
     * @param  \App\Http\Requests\Admin\ListEcommerceProductRequest  $request  Validated request instance.
     */
    public function search(ListEcommerceProductRequest $request): JsonResponse
    {
        $products = $this->productService->search($request);

        return $this->returnJsonResponse(
            message: 'Products successfully fetched.',
            data: $products
        );
    }

    /**
     * Soft delete the specified e-commerce product.
     *
     * @param  DeleteEcommerceProductRequest  $request  The HTTP request for deleting the product.
     * @param  EcommerceProduct  $product  The product instance to be deleted.
     * @return JsonResponse JSON response confirming the deletion.
     */
    public function destroy(DeleteEcommerceProductRequest $request, EcommerceProduct $product)
    {
        $product->delete();

        return $this->returnJsonResponse(
            message: 'Product successfully deleted.',
        );
    }
}
