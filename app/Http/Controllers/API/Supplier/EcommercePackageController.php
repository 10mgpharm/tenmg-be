<?php

namespace App\Http\Controllers\API\Supplier;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supplier\DeleteEcommercePackageRequest;
use App\Http\Requests\Supplier\ListEcommercePackageRequest;
use App\Http\Requests\Supplier\ShowEcommercePackageRequest;
use App\Http\Requests\Supplier\StoreEcommercePackageRequest;
use App\Http\Requests\Supplier\UpdateEcommercePackageRequest;
use App\Http\Resources\EcommercePackageResource;
use App\Models\EcommercePackage;
use App\Services\Admin\EcommercePackageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class EcommercePackageController extends Controller
{
    public function __construct(private EcommercePackageService $packageService) {}

    /**
     * List all ecommerce packages.
     *
     * @param ListEcommercePackageRequest $request
     * @return JsonResponse
     */
    public function index(ListEcommercePackageRequest $request): JsonResponse
    {
        $packages = EcommercePackage::latest()->businesses()->paginate();

        return $this->returnJsonResponse(
            message: 'packages successfully fetched.',
            data: EcommercePackageResource::collection($packages)->response()->getData(true)
        );
    }

    /**
     * Store a new ecommerce package.
     *
     * @param StoreEcommercePackageRequest $request
     * @return JsonResponse
     */
    public function store(StoreEcommercePackageRequest $request): JsonResponse
    {
        $package = $this->packageService->store(
            $request->validated(),
            $request->user()
        );

        return $package
            ? $this->returnJsonResponse(
                message: 'package successfully created.',
                data: new EcommercePackageResource($package)
            )
            : $this->returnJsonResponse(
                message: 'Oops, cannot create package at the moment. Please try again later.'
            );
    }

    /**
     * Show an ecommerce package.
     *
     * @param ShowEcommercePackageRequest $request
     * @return JsonResponse
     */
    public function show(ShowEcommercePackageRequest $request, EcommercePackage $package): JsonResponse
    {
        return $package
            ? $this->returnJsonResponse(
                message: 'Package successfully fetched.',
                data: new EcommercePackageResource($package)
            )
            : $this->returnJsonResponse(
                message: 'Oops, cannot fetch package at the moment. Please try again later.'
            );
    }

    /**
     * Update an existing ecommerce package.
     *
     * @param UpdateEcommercePackageRequest $request
     * @param EcommercePackage $package
     * @return JsonResponse
     */
    public function update(UpdateEcommercePackageRequest $request, EcommercePackage $package): JsonResponse
    {
        $isUpdated = $this->packageService->update(
            $request->validated(),
            $request->user(),
            $package
        );

        return $isUpdated
            ? $this->returnJsonResponse(
                message: 'package successfully updated.',
                data: new EcommercePackageResource($package->refresh())
            )
            : $this->returnJsonResponse(
                message: 'Oops, cannot update package at the moment. Please try again later.'
            );
    }

    /**
     * Delete an ecommerce package.
     *
     * @param DeleteEcommercePackageRequest $request
     * @param EcommercePackage $package
     * @return JsonResponse
     */
    public function destroy(DeleteEcommercePackageRequest $request, EcommercePackage $package): JsonResponse
    {
        $isDeleted = $this->packageService->delete($package);

        return $isDeleted
            ? $this->returnJsonResponse(
                message: 'package successfully deleted.',
                statusCode: Response::HTTP_OK
            )
            : $this->returnJsonResponse(
                message: 'Cannot delete this package because it has associated products.',
                statusCode: Response::HTTP_BAD_REQUEST
            );
    }
}
