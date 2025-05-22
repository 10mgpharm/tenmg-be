<?php

namespace App\Http\Controllers\API\Admin;

use App\Enums\StatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DeleteEcommerceCategoryRequest;
use App\Http\Requests\Admin\ListEcommerceCategoryRequest;
use App\Http\Requests\Admin\ShowEcommerceCategoryRequest;
use App\Http\Requests\Admin\StoreEcommerceCategoryRequest;
use App\Http\Requests\Admin\UpdateEcommerceCategoryRequest;
use App\Http\Resources\EcommerceCategoryResource;
use App\Models\EcommerceCategory;
use App\Services\Admin\EcommerceCategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class EcommerceCategoryController extends Controller
{
    public function __construct(private EcommerceCategoryService $categoryService) {}

    /**
     * List all ecommerce categories.
     *
     * This method retrieves a paginated list of all ecommerce categories sorted by the latest creation date.
     * The list is returned in a JSON response, including the paginated metadata.
     *
     * @param  ListEcommerceCategoryRequest  $request  Validated request instance for listing categories.
     * @return JsonResponse Returns a JSON response with the list of categories and a success message.
     */
    public function index(ListEcommerceCategoryRequest $request): JsonResponse
    {
        $categoriesQuery = EcommerceCategory::query()
            ->when($request->input('search'), function ($query, $search) {
                $query->where('name', 'like', "%{$search}%");
            })
              // Filter by product status (e.g., ACTIVE, INACTIVE)
            ->when(
                $request->input('status'),
                fn($query, $status) => $query->whereIn(
                    'status', 
                    is_array($status)
                        ? array_unique(array_merge(...array_map(fn($s) => StatusEnum::mapper(trim($s)), $status)))
                        : array_unique(array_merge(...array_map(fn($s) => StatusEnum::mapper(trim($s)), explode(",", $status))))
                )
            )
            
            // Filter by active status (active/inactive mapped to 1/0)
            ->when(
                $request->input('active'),
                fn($query, $active) =>
                $query->where('active', '=', $active == 'active' ? 1 : 0)
            );


        if ($request->has('sort') && $request->has('order')) {
            $sortColumn = $request->input('sort');
            $sortOrder = $request->input('order');

            $validColumns = ['name'];
            if (in_array($sortColumn, $validColumns) && in_array(strtolower($sortOrder), ['asc', 'desc'])) {
                $categoriesQuery->orderBy($sortColumn, $sortOrder);
            }
        } else {
            $categoriesQuery->orderBy('created_at', 'desc');
        }

        $categories = $categoriesQuery
            ->paginate($request->has('perPage') ? $request->perPage : 10)
            ->withQueryString()
            ->through(fn (EcommerceCategory $item) => EcommerceCategoryResource::make($item));

        return $this->returnJsonResponse(
            message: 'Categories successfully fetched.',
            data: $categories
        );
    }

    /**
     * Store a new ecommerce category.
     *
     * This method validates the incoming request, creates a new ecommerce category using the validated data,
     * and returns a JSON response with the details of the newly created category.
     *
     * @param  StoreEcommerceCategoryRequest  $request  Validated request instance containing data for the new category.
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
     * Show an ecommerce category.
     */
    public function show(ShowEcommerceCategoryRequest $request, EcommerceCategory $category): JsonResponse
    {
        return $category
            ? $this->returnJsonResponse(
                message: 'Category successfully fetched.',
                data: new EcommerceCategoryResource($category)
            )
            : $this->returnJsonResponse(
                message: 'Oops, can\'t view category at the moment. Please try again later.'
            );
    }

    /**
     * Update an existing ecommerce category.
     *
     * This method validates the incoming request, updates the specified category with the new data,
     * and returns a JSON response with the updated category's details.
     *
     * @param  UpdateEcommerceCategoryRequest  $request  Validated request instance containing updated data for the category.
     * @param  EcommerceCategory  $category  The category to be updated.
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

    /**
     * Delete an ecommerce category.
     *
     * @param  EcommerceCategory  $category  The category to be deleted.
     * @return JsonResponse Returns a JSON response indicating success or failure.
     */
    public function destroy(DeleteEcommerceCategoryRequest $request, EcommerceCategory $category): JsonResponse
    {
        $isDeleted = $this->categoryService->delete($category);

        if (! $isDeleted) {
            return $this->returnJsonResponse(
                message: 'Cannot delete this category because it has associated products.',
                statusCode: Response::HTTP_BAD_REQUEST,
            );
        }

        return $this->returnJsonResponse(
            message: 'Category successfully deleted.',
            statusCode: Response::HTTP_OK,
        );
    }
}
