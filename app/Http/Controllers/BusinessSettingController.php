<?php

namespace App\Http\Controllers;

use App\Enums\InAppNotificationType;
use App\Http\Requests\BusinessSettingAccountSetupRequest;
use App\Http\Requests\BusinessSettingLicenseWithdrawalRequest;
use App\Http\Requests\BusinessSettingPersonalInformationRequest;
use App\Http\Requests\ShowBusinessSettingRequest;
use App\Http\Resources\BusinessResource;
use App\Models\Business;
use App\Models\User;
use App\Services\AttachmentService;
use App\Services\InAppNotificationService;
use App\Services\Lender\BankAccountService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class BusinessSettingController extends Controller
{
    public function __construct(private AttachmentService $attachmentService, private BankAccountService $bankAccountService) {}

    /**
     * Display the business details associated with the authenticated user.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(ShowBusinessSettingRequest $request)
    {
        $user = $request->user()->load('ownerBusinessType.owner');
        $business = $user->ownerBusinessType ?? $user->businesses()?->first();

        return $this->returnJsonResponse(
            message: 'Business details successfully retrieved.',
            data: (new BusinessResource($business))
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
            array_flip(['name', 'contact_person', 'contact_phone', 'contact_email', 'address', 'contact_person_position'])
        ));  // since fillable isn't used.

        $business = $user->ownerBusinessType ?? $user->businesses()?->first();
        $business->update($data);
        $business->refresh();

        return $this->returnJsonResponse(
            message: 'Business information successfully updated.',
            data: (new BusinessResource($business))
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

        $admins = User::role('admin')->get();

        $validated = $request->validated();
        $user = $request->user();
        $business = $user->ownerBusinessType ?? $user->businesses()?->first();

        $data = array_filter(array_intersect_key(
            $validated,
            array_flip(['license_number', 'expiry_date'])
        ));  // since fillable isn't used.

        // Save uploaded file
        if ($request->hasFile('cacDocument')) {
            $created = $this->attachmentService->saveNewUpload(
                $request->file('cacDocument'),
                $business?->id,
                Business::class,
            );

            $data['cac_document_id'] = $created->id;
            $data['expiry_date'] = $request->expiryDate;
        }

        $updated = $business?->update($data);
        if ($updated) {
            $business?->update([
                'license_verification_status' => 'PENDING'
            ]);
        }

        $ownerBusiness = $business;
        $licenseFile = $ownerBusiness->cac ?? null;

        $user->sendLicenseVerificationNotification("We’ve received your license verification request, and it is currently under review. You will receive a response from us shortly.", Auth::user());

        // Send a license upload notification
        (new InAppNotificationService)
        ->notify(InAppNotificationType::LICENSE_UPLOAD);

        $role = $user->getRoleNames()->first();

        //send mail to all the admins
        foreach ($admins as $admin) {
            $admin->sendLicenseVerificationNotification("A **{$role}** has submitted their license for verification, and it is now awaiting review. Kindly proceed with the review and verification of the submitted license.", $admin);
        }
        // Send a license upload notification to admins
        (new InAppNotificationService)
        ->forUsers($admins)
        ->notify(InAppNotificationType::ADMIN_LICENSE_UPLOAD, ['role' => $role]);

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
    public function getBusinessStatus(Request $request)//admin@10mg.com
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

    /**
     * Withdraw a license uploaded by the business.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function withdraw(BusinessSettingLicenseWithdrawalRequest $request)
    {
        $user = $request->user();
        $ownerBusiness = $user->ownerBusinessType;

        $licenseFile = $ownerBusiness?->cac_document ?? null;

        if ($licenseFile) {
            $isDeleted = $this->attachmentService->deleteFile($licenseFile);

            $ownerBusiness?->update([
                'license_verification_status' => NULL,
            ]);

            $ownerBusiness?->refresh();

            return $this->returnJsonResponse(
                message: 'Business license upload successfully withdrawn.',
                data: [
                    'license_number' => $ownerBusiness?->license_number,
                    'expiry_date' => $ownerBusiness?->expiry_date,
                    'license_file' => null,
                    'license_verification_status' => $ownerBusiness?->license_verification_status,
                ]
            );
        }

        return $this->returnJsonResponse(
            message: 'Oops, can\'t update license at the moment. Please try again later.'
        );
    }

    public function businessBankAccount(Request $request)
    {

        $request->validate([
            'bankName' => 'required|string',
            'bankCode' => 'required',
            'accountName' => 'required',
            'accountNumber' => 'required|digits:10',
            'bvn' => 'required|digits:10'
        ]);

        $bankData = $this->bankAccountService->addUpdateBankAccount($request);

        return $this->returnJsonResponse(
            message: 'Business account created/updated',
            data: $bankData
        );

    }
}
