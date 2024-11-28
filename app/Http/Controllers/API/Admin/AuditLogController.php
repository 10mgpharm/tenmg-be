<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\AuditLogResource;
use App\Models\AuditLog;
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
    public function index(): JsonResponse
    {
        $logs = AuditLog::latest()->paginate(20);

        return $this->returnJsonResponse(
            message: 'Logs successfully fetched.',
            data: AuditLogResource::collection($logs)
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
    public function search(Request $request): JsonResponse
    {
        $params = $request->only(['crud_type']);

        $query = AuditLog::where($params);

        if ($event = $request->input('event')) {
            $query->where('event', 'LIKE', '%'.$event.'%');
        }

        if ($ip = $request->input('ip')) {
            $query->where('ip', 'LIKE', '%'.$ip.'%');
        }

        $from = $request->input('from');
        $to = $request->input('to');

        if ($from || $to) {
            $from = $from ? Carbon::parse($from) : null;
            $to = $to ? Carbon::parse($to) : null;

            if ($to && $to->format('H:i:s') === '00:00:00') {
                $to->endOfDay();
            }

            $query->when($from, fn ($q) => $q->where('created_at', '>=', $from))
                ->when($to, fn ($q) => $q->where('created_at', '<=', $to));
        }

        $logs = $query->paginate(20);

        return $this->returnJsonResponse(
            message: 'Logs successfully fetched.',
            data: AuditLogResource::collection($logs)
        );
    }
}
