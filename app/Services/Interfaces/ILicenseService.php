<?php

namespace App\Services\Interfaces;

use App\Http\Requests\Admin\ListBusinessLicenseRequest;
use App\Models\Business;
use Illuminate\Pagination\LengthAwarePaginator;

interface ILicenseService
{
    /**
     * Retrieve a paginated collection of business licenses based on filters.
     *
     * @param  \App\Http\Requests\Admin\ListBusinessLicenseRequest  $request  Validated request instance.
     * @return \Illuminate\Pagination\LengthAwarePaginator Paginated list of businesses with applied filters.
     */
    public function index(ListBusinessLicenseRequest $request): LengthAwarePaginator;

    /**
     * Update the status of an existing business license in the database.
     *
     * @param  array  $validated  The validated data for updating the business license.
     * @param  \App\Models\Business $business The license to update.
     * @return bool Returns true if the business license status was updated, false on failure.
     */
    public function update(array $validated, Business $business): bool;
}
