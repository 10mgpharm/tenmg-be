<?php

namespace App\Http\Controllers;

use App\Http\Requests\BusinessSettingAccountSetupRequest;
use App\Http\Requests\BusinessSettingPersonalInformationRequest;
use App\Http\Requests\ShowBusinessSettingRequest;
use App\Http\Resources\BusinessResource;
use App\Models\Business;
use App\Models\User;
use App\Services\AttachmentService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class BusinessSettingController extends Controller
{
    public function __construct(private AttachmentService $attachmentService) {}

    /**
     * Display the business details associated with the authenticated user.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(ShowBusinessSettingRequest $request)
    {
        $user = $request->user()->load('ownerBusinessType.owner');

        return $this->returnJsonResponse(
            message: 'Business details successfully retrieved.',
            data: (new BusinessResource($user->ownerBusinessType))
        );
    }

    /**
     * Update the business personal information details associated with the authenticated user.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function businessInformation(BusinessSettingPersonalInformationRequest $request)
    {
        $validated = $request->validated();
        $user = $request->user()->load('ownerBusinessType.owner');

        $data = array_filter(array_intersect_key(
            $validated,
            array_flip(['name', 'contact_person', 'contact_phone', 'contact_email', 'address'])
        ));  // since fillable isn't used.

        $user->ownerBusinessType()->update($data);
        $user->ownerBusinessType->refresh();

        return $this->returnJsonResponse(
            message: 'Business information successfully updated.',
            data: (new BusinessResource($user->ownerBusinessType))
        );
    }

    /**
     * Update the business account license number, expiry date and cac doc
     * details associated with the authenticated user.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function license(BusinessSettingAccountSetupRequest $request)
    {
        $validated = $request->validated();
        $user = $request->user();

        $data = array_filter(array_intersect_key(
            $validated,
            array_flip(['license_number', 'expiry_date'])
        ));  // since fillable isn't used.

        // Save uploaded file
        if ($request->hasFile('cacDocument')) {
            $created = $this->attachmentService->saveNewUpload(
                $request->file('cacDocument'),
                $user->ownerBusinessType->id,
                Business::class,
            );

            $data['cac_document_id'] = $created->id;
        }

        $updated = $user->ownerBusinessType()->update($data);
        if ($updated) {
            $user->ownerBusinessType()->update([
                'license_verification_status' => 'PENDING',
            ]);
        }

        $ownerBusiness = $user->ownerBusinessType()->first();
        $licenseFile = $ownerBusiness->cac ?? null;

        return $this->returnJsonResponse(
            message: 'Business license successfully updated.',
            data: [
                'license_number' => $ownerBusiness?->license_number,
                'expiry_date' => $ownerBusiness?->expiry_date,
                'license_file' => $licenseFile,
                'license_verification_status' => $ownerBusiness?->license_verification_status,
            ]
        );
    }

    /**
     * Get license status and cac doc details associated with the authenticated user.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getBusinessStatus(Request $request)
    {
        $user = $request->user();
        $ownerBusiness = $user->ownerBusinessType;

        if (! $ownerBusiness) {
            return $this->returnJsonResponse(
                message: 'You do not have access to view this business resource. Only business owner is permitted',
                data: null,
                statusCode: Response::HTTP_UNAUTHORIZED,
            );
        }

        $licenseFile = $ownerBusiness?->cac ?? null;

        return $this->returnJsonResponse(
            message: 'Business status successfully retrieved.',
            data: [
                'license_number' => $ownerBusiness?->license_number,
                'expiry_date' => $ownerBusiness?->expiry_date,
                'license_file' => $licenseFile,
                'license_verification_status' => $ownerBusiness?->license_verification_status,
            ]
        );
    }
}
