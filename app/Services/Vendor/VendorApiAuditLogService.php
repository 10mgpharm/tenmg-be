<?php

namespace App\Services\Vendor;

use App\Models\ApiCallLog;

class VendorApiAuditLogService
{

    public function getApiLogs(array $filters, int $perPage = 15):\Illuminate\Contracts\Pagination\LengthAwarePaginator
    {

        $user = request()->user();
        $business_id = $user->ownerBusinessType?->id
            ?: $user->businesses()->firstWhere('user_id', $user->id)?->id;

        $query = ApiCallLog::query();

        $query->when(isset($filters['search']), function ($query) use ($filters) {
            $searchTerm = "%{$filters['search']}%";
            return $query->where(function ($query) use ($searchTerm) {
                $query->where('identifier', 'like', $searchTerm)->orWhere('event', 'like', $searchTerm);
            });
        });

        $query->where('business_id', $business_id)->orderBy('created_at', 'desc');

        return $query->paginate($perPage);
    }

}