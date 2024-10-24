<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ListEcommerceCategoryRequest;
use App\Http\Requests\Admin\StoreEcommerceCategoryRequest;
use App\Http\Requests\Admin\UpdateEcommerceCategoryRequest;
use App\Http\Resources\EcommerceCategoryResource;
use App\Models\EcommerceCategory;
use App\Services\Admin\EcommerceCategoryService;
use Illuminate\Http\JsonResponse;

class EcommerceCategoryController extends Controller
{
    public function __construct(private EcommerceCategoryService $categoryService) {}

    /**
     * List all ecommerce categories.
     *
     * This method retrieves a paginated list of all ecommerce categories sorted by the latest creation date.
     * The list is returned in a JSON response, including the paginated metadata.
     *
     * @param ListEcommerceCategoryRequest $request Validated request instance for listing categories.
     * @return JsonResponse Returns a JSON response with the list of categories and a success message.
     */
    public function index(ListEcommerceCategoryRequest $request): JsonResponse
    {
        $categories = EcommerceCategory::latest()->paginate();

        return $this->returnJsonResponse(
            message: 'Categories successfully fetched.',
            data: EcommerceCategoryResource::collection($categories)->response()->getData(true)
        );
    }

    /**
     * Store a new ecommerce category.
     *
     * This method validates the incoming request, creates a new ecommerce category using the validated data,
     * and returns a JSON response with the details of the newly created category.
     *
     * @param StoreEcommerceCategoryRequest $request Validated request instance containing data for the new category.
     * @return JsonResponse Returns a JSON response with the created category's details or an error message if the process fails.
     */
    public function store(StoreEcommerceCategoryRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $user = $request->user();

        $category = $this->categoryService->store($validated, $user);

        if (! $category) {
            return $this->returnJsonResponse(
                message: 'Oops, can\'t create category at the moment. Please try again later.'
            );
        }

        return $this->returnJsonResponse(
            message: 'Category successfully created.',
            data: new EcommerceCategoryResource($category)
        );
    }

    /**
     * Update an existing ecommerce category.
     *
     * This method validates the incoming request, updates the specified category with the new data, 
     * and returns a JSON response with the updated category's details.
     *
     * @param UpdateEcommerceCategoryRequest $request Validated request instance containing updated data for the category.
     * @param EcommerceCategory $category The category to be updated.
     * @return JsonResponse Returns a JSON response with the updated category's details or an error message if the process fails.
     */
    public function update(UpdateEcommerceCategoryRequest $request, EcommerceCategory $category): JsonResponse
    {
        $validated = $request->validated();
        $user = $request->user();

        $isUpdated = $this->categoryService->update($validated, $user, $category);

        if (! $isUpdated) {
            return $this->returnJsonResponse(
                message: 'Oops, can\'t update category at the moment. Please try again later.'
            );
        }

        return $this->returnJsonResponse(
            message: 'Category successfully updated.',
            data: new EcommerceCategoryResource($category->refresh())
        );
    }
}
