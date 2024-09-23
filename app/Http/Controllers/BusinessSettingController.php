<?php

namespace App\Http\Controllers;

use App\Http\Requests\BusinessSettingAccountSetupRequest;
use App\Services\AttachmentService;
use App\Http\Resources\BusinessResource;
use App\Http\Requests\ShowBusinessSettingRequest;
use App\Http\Requests\BusinessSettingPersonalInformationRequest;
use App\Http\Resources\UserResource;
use App\Models\Business;
use App\Models\User;

class BusinessSettingController extends Controller
{
    public function __construct(private AttachmentService $attachmentService,) {}

    /**
     * Display the business details associated with the authenticated user.
     *
     * @param \App\Http\Requests\ShowBusinessSettingRequest $request
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
     * @param \App\Http\Requests\BusinessSettingPersonalInformationRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function personalInformation(BusinessSettingPersonalInformationRequest $request)
    {
        $validated = $request->validated();
        $user = $request->user()->load('ownerBusinessType.owner');

        $data = array_filter(array_intersect_key(
            $validated,
            array_flip(['name', 'contact_person', 'contact_phone', 'contact_email', 'address'])
        ));  // since fillable isn't used.

        // Save uploaded file
        if($request->hasFile('profilePicture')){
            $created = $this->attachmentService->saveNewUpload(
                $request->file('profilePicture'),
                $user->id,
                User::class,
            );
            
            $user->update(['avatar_id' => $created->id]);
        }

        $user->ownerBusinessType()->update($data);
        $user->ownerBusinessType->refresh();

        return $this->returnJsonResponse(
            message: 'Business personal information details successfully updated.',
            data: (new BusinessResource($user->ownerBusinessType))
        );
    }

    /**
     * Update the business account license number, expiry date and cac doc 
     * details associated with the authenticated user.
     *
     * @param \App\Http\Requests\BusinessSettingAccountSetupRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function accountSetup(BusinessSettingAccountSetupRequest $request)
    {
        $validated = $request->validated();
        $user = $request->user();

        $data = array_filter(array_intersect_key(
            $validated,
            array_flip(['license_number', 'expiry_date'])
        ));  // since fillable isn't used.

         // Save uploaded file
        if($request->hasFile('cacDocument')){
            $created = $this->attachmentService->saveNewUpload(
                $request->file('cacDocument'),
                $user->ownerBusinessType->id,
                Business::class,
            );
            
            $data['cac_document_id'] = $created->id;
        }

        $user->ownerBusinessType()->update($data);

        return $this->returnJsonResponse(
            message: 'Business account setup details successfully updated.',
            data: (new BusinessResource($user->ownerBusinessType->refresh()))
        );
    }
}
