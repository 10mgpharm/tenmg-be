<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DeleteEcommerceBrandRequest;
use App\Http\Requests\Admin\ListEcommerceBrandRequest;
use App\Http\Requests\Admin\ShowEcommerceBrandRequest;
use App\Http\Requests\Admin\StoreEcommerceBrandRequest;
use App\Http\Requests\Admin\UpdateEcommerceBrandRequest;
use App\Http\Resources\EcommerceBrandResource;
use App\Models\EcommerceBrand;
use App\Services\Admin\EcommerceBrandService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class EcommerceBrandController extends Controller
{
    public function __construct(private EcommerceBrandService $brandService) {}

    /**
     * List all ecommerce brands.
     *
     * This method retrieves a paginated list of all ecommerce brands sorted by the latest creation date.
     * The list is returned in a JSON response, including the paginated metadata.
     *
     * @param ListEcommerceBrandRequest $request Validated request instance for listing brands.
     * @return JsonResponse Returns a JSON response with the list of brands and a success message.
     */
    public function index(ListEcommerceBrandRequest $request): JsonResponse
    {
        $brands = EcommerceBrand::latest()->paginate();

        return $this->returnJsonResponse(
            message: 'Brands successfully fetched.',
            data: EcommerceBrandResource::collection($brands)->response()->getData(true)
        );
    }

    /**
     * Store a new ecommerce brand.
     *
     * This method validates the incoming request, creates a new ecommerce brand using the validated data,
     * and returns a JSON response with the details of the newly created brand.
     *
     * @param StoreEcommerceBrandRequest $request Validated request instance containing data for the new brand.
     * @return JsonResponse Returns a JSON response with the created brand's details or an error message if the process fails.
     */
    public function store(StoreEcommerceBrandRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $user = $request->user();

        $brand = $this->brandService->store($validated, $user);

        if (! $brand) {
            return $this->returnJsonResponse(
                message: 'Oops, can\'t create brand at the moment. Please try again later.'
            );
        }

        return $this->returnJsonResponse(
            message: 'Brand successfully created.',
            data: new EcommerceBrandResource($brand)
        );
    }

    /**
     * Update an existing ecommerce brand.
     *
     * This method validates the incoming request, updates the specified brand with the new data, 
     * and returns a JSON response with the updated brand's details.
     *
     * @param UpdateEcommerceBrandRequest $request Validated request instance containing updated data for the brand.
     * @param EcommerceBrand $brand The brand to be updated.
     * @return JsonResponse Returns a JSON response with the updated brand's details or an error message if the process fails.
     */
    public function update(UpdateEcommerceBrandRequest $request, EcommerceBrand $brand): JsonResponse
    {
        $validated = $request->validated();
        $user = $request->user();

        $isUpdated = $this->brandService->update($validated, $user, $brand);

        if (! $isUpdated) {
            return $this->returnJsonResponse(
                message: 'Oops, can\'t update brand at the moment. Please try again later.'
            );
        }

        return $this->returnJsonResponse(
            message: 'Brand successfully updated.',
            data: new EcommerceBrandResource($brand->refresh())
        );
    }

    /**
     * Show an ecommerce brand.
     *
     * @param ShowEcommerceBrandRequest $request
     * @return JsonResponse
     */
    public function show(ShowEcommerceBrandRequest $request, EcommerceBrandResource $brand): JsonResponse
    {
        return $brand
            ? $this->returnJsonResponse(
                message: 'Brand successfully fetched.',
                data: new EcommerceBrandResource($brand)
            )
            : $this->returnJsonResponse(
                message: 'Oops, can\'t view brand at the moment. Please try again later.'
            );
    }

    /**
     * Delete an ecommerce brand.
     *
     * @param EcommerceBrand $brand The brand to be deleted.
     * @return JsonResponse Returns a JSON response indicating success or failure.
     */
    public function destroy(DeleteEcommerceBrandRequest $request, EcommerceBrand $brand): JsonResponse
    {
        $isDeleted = $this->brandService->delete($brand);

        if (! $isDeleted) {
            return $this->returnJsonResponse(
                message: 'Cannot delete this brand because it has associated products.',
                statusCode: Response::HTTP_BAD_REQUEST,
            );
        }

        return $this->returnJsonResponse(
            message: 'Brand successfully deleted.',
            statusCode: Response::HTTP_OK,
        );
    }
}
