<?php

namespace App\Services\Admin;

use App\Http\Requests\Admin\ListBusinessLicenseRequest;
use App\Models\Business;
use App\Services\Interfaces\ILicenseService;
use Exception;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class LicenseService implements ILicenseService
{
    /**
     * Retrieve a paginated collection of business licenses based on filters.
     *
     * @param  \App\Http\Requests\Admin\ListBusinessLicenseRequest  $request  Validated request instance.
     * @return \Illuminate\Pagination\LengthAwarePaginator Paginated list of businesses with applied filters.
     */
    public function index(ListBusinessLicenseRequest $request): LengthAwarePaginator
    {
        $query = Business::query();

        if ($status = $request->input('status')) {
            $query->where('status', strtoupper($status));
        }

        if ($name = $request->input('businessName')) {
            $query->where('name', 'LIKE', '%' . $name . '%');
        }

        if ($contactEmail = $request->input('contactEmail')) {
            $query->where('contact_email', 'LIKE', '%' . $contactEmail . '%');
        }

        if ($licenseNumber = $request->input('licenseNumber')) {
            $query->where('license_number', 'LIKE', '%' . $licenseNumber . '%');
        }

        if ($request->input('type') != null) {
            if($request->input('type') != "all"){
                $query->where('type', 'LIKE', '%' . $request->type . '%');
            }
        }

        return $query->latest()->paginate();
    }

    /**
     * Update the status of an existing business license in the database.
     *
     * @param  array  $validated  The validated data for updating the license.
     * @param  \App\Models\Business  $license  The license to update.
     * @return bool Returns true if the license status was updated, false on failure.
     * @throws \Exception If the transaction fails.
     */
    public function update(array $validated, Business $business): bool
    {
        try {
            $trans = DB::transaction(
                fn() => $business->update([
                    'license_verification_status' => $validated['license_verification_status'],
                    'license_verification_comment' => $validated['license_verification_comment'],
                ])
            );

            $user = $business->owner;
            //send notification mail to user
            if ($validated['license_verification_status'] == 'APPROVED') {
                //send notification mail to user

                $user->sendLicenseVerificationNotification('Your license verification has been successfully approved. You now have full access.');
            }else{
                $user->sendLicenseVerificationNotification('Your license verification request has been denied for the following reason:'.'\n'. $validated['license_verification_comment']);
            }

            return $trans;

        } catch (Exception $e) {
            throw new Exception('Failed to update the license status: ' . $e->getMessage());
        }
    }
}
