<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ListBusinessLicenseRequest;
use App\Http\Requests\Admin\UpdateBusinessLicenseStatusRequest;
use App\Http\Resources\Admin\BusinessLicenseResource;
use App\Models\Business;
use App\Services\Admin\LicenseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class BusinessLicenseController extends Controller
{
    /**
     * BusinessLicenseController constructor.
     *
     * @param \App\Services\Admin\LicenseService $licenseService
     */
    public function __construct(private LicenseService $licenseService) {}

    /**
     * Retrieve a paginated list of licenses.
     *
     * @param  \App\Http\Requests\Admin\ListBusinessLicenseRequest  $request  Validated request instance.
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(ListBusinessLicenseRequest $request): JsonResponse
    {

        $businesses = $this->licenseService->index($request);

        return $this->returnJsonResponse(
            message: 'business Licenses successfully fetched.',
            data: $businesses
        );
    }

    /**
     * Update the status of a specific license.
     *
     * @param  \App\Http\Requests\Admin\UpdateBusinessLicenseStatusRequest $request Validated request instance.
     * @param  \App\Models\License $license The license to be updated.
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateBusinessLicenseStatusRequest $request, Business $business): JsonResponse
    {
        $updated = $this->licenseService->update($request->validated(), $business);

        if (!$updated) {
            return $this->returnJsonResponse(
                message: 'Could not update business license status. Please try again later.'
            );
        }

        return $this->returnJsonResponse(
            message: 'License status successfully updated.',
            data: new BusinessLicenseResource($business->refresh())
        );
    }
}
