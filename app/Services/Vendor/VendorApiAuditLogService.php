<?php

namespace App\Services\Vendor;

use App\Models\ApiCallLog;
use App\Models\WebHookCallLog;

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
                $query->where('route', 'like', $searchTerm)->orWhere('event', 'like', $searchTerm)->orWhere('response', 'like', $searchTerm);
            });
        });

        $query->when(isset($filters['status']), function ($query) use ($filters) {
            return $query->where('status', $filters['status']);
        });

        $query->when(
            isset($criteria['dateFrom']) && isset($criteria['dateTo']),
            function ($query) use ($filters) {
                // Parse dates with Carbon to ensure proper format
                $dateFrom = \Carbon\Carbon::parse($filters['dateFrom'])->startOfDay();
                $dateTo = \Carbon\Carbon::parse($filters['dateTo'])->endOfDay();

                return $query->whereBetween('created_at', [$dateFrom, $dateTo]);
            }
        );

        $query->where('business_id', $business_id)->orderBy('created_at', 'desc');

        return $query->paginate($perPage);
    }

    public function getWebHookLogs(array $filters, int $perPage = 15):\Illuminate\Contracts\Pagination\LengthAwarePaginator
    {

        $user = request()->user();
        $business_id = $user->ownerBusinessType?->id
            ?: $user->businesses()->firstWhere('user_id', $user->id)?->id;

        $query = WebHookCallLog::query();

        $query->when(isset($filters['search']), function ($query) use ($filters) {
            $searchTerm = "%{$filters['search']}%";
            return $query->where(function ($query) use ($searchTerm) {
                $query->where('route', 'like', $searchTerm)->orWhere('event', 'like', $searchTerm)->orWhere('response', 'like', $searchTerm);
            });
        });

        $query->when(isset($filters['status']), function ($query) use ($filters) {
            return $query->where('status', $filters['status']);
        });

        $query->when(
            isset($criteria['dateFrom']) && isset($criteria['dateTo']),
            function ($query) use ($filters) {
                // Parse dates with Carbon to ensure proper format
                $dateFrom = \Carbon\Carbon::parse($filters['dateFrom'])->startOfDay();
                $dateTo = \Carbon\Carbon::parse($filters['dateTo'])->endOfDay();

                return $query->whereBetween('created_at', [$dateFrom, $dateTo]);
            }
        );

        $query->where('business_id', $business_id)->orderBy('created_at', 'desc');

        return $query->paginate($perPage);

    }

}
