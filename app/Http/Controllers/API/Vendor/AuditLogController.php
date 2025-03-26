<?php

namespace App\Http\Controllers\API\Vendor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Vendor\SearchAuditLogRequest;
use App\Http\Resources\Admin\AuditLogResource;
use App\Models\Activity;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    /**
     * Display a paginated list of audit logs in descending order.
     *
     * @return JsonResponse JSON response containing the paginated audit logs.
     */
    public function index(SearchAuditLogRequest $request): JsonResponse
    {
        $user = $request->user();
        $business_id = $user->ownerBusinessType?->id ?? $user->businesses()->firstWhere('user_id', $user->id)?->id;

        $query = Activity::whereJsonContains('properties->actor_business_id', $business_id)
            ->when(
                $request->input('event'),
                fn($query, $vent) =>
                $query->where('event', 'like', "%{$vent}%")
            )
            ->when(
                $request->input('action'),
                fn($query, $action) =>
                $query->where('properties->action', 'like', "%{$action}%")
            )
            ->when(
                $request->input('crudType'),
                fn($query, $crud_type) => $query->whereIn(
                    'properties->crud_type',
                    array_unique(
                        array_map(fn($s) => trim($s), is_array($crud_type) ? $crud_type : explode(",", $crud_type))
                    )
                )
            )            
            ->when(
                $request->input('ip'),
                fn($query, $ip) => $query->whereIn(
                    'properties->ip_address',
                    array_unique(
                        array_map(fn($s) => trim($s), is_array($ip) ? $ip : explode(",", $ip))
                    )
                )
            )            
            ->when(
                $request->input('fromDate'),
                fn($query, $from) =>
                $query->where('created_at', '>=', Carbon::parse($from)->startOfDay())
            )
            ->when(
                $request->input('toDate'),
                fn($query, $to) =>
                $query->where('created_at', '<=', Carbon::parse($to)->endOfDay())
            );



        // Sort by specified column and order (default: created_at desc)
        if ($request->has('sort') && $request->has('order')) {
            $sortColumn = $request->input('sort');
            $sortOrder = $request->input('order');

            $validColumns = ['name']; // Define valid sortable columns
            if (in_array($sortColumn, $validColumns) && in_array(strtolower($sortOrder), ['asc', 'desc'])) {
                $query->orderBy($sortColumn, $sortOrder);
            }
        } else {
            $query->orderBy('created_at', 'desc');
        }

        // Return paginated results with applied filters and transformations
        $logs = $query
            ->paginate($request->has('perPage') ? $request->perPage : 20)
            ->withQueryString()
            ->through(fn(Activity $item) => AuditLogResource::make($item));

        return $this->returnJsonResponse(
            message: 'Vendor logs successfully fetched.',
            data: $logs
        );
    }

    /**
     * Search or filter audit logs based on provided criteria.
     *
     * @param  Request  $request  The HTTP request containing filter parameters.
     *
     * Filterable fields:
     * - `crud_type` (string): Filter logs by CRUD operation type.
     * - `event` (string): Search logs where the event field matches the given input.
     * - `ip` (string): Search logs where the IP address matches the given input.
     * - `from` and `to` (date): Filter logs from a specific date, to a specific date, or within a range.
     * @return JsonResponse JSON response containing the filtered audit logs.
     */
    public function search(SearchAuditLogRequest $request): JsonResponse
    {
        return $this->index($request);
    }
}
