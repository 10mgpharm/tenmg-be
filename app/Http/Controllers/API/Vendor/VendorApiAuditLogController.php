<?php

namespace App\Http\Controllers\API\Vendor;

use App\Http\Controllers\Controller;
use App\Http\Resources\APILogsResource;
use App\Models\ApiCallLog;
use App\Services\Vendor\VendorApiAuditLogService;
use Illuminate\Http\Request;

class VendorApiAuditLogController extends Controller
{

    function __construct(private VendorApiAuditLogService $vendorApiAuditLogService)
    {
        
    }

    public function getApiLogs(Request $request)
    {

        $apiLogs = $this->vendorApiAuditLogService->getApiLogs($request->all(), $request->perPage ?? 10);

        return $this->returnJsonResponse(
            data: APILogsResource::collection($apiLogs)->response()->getData(true)
        );

    }
    
}
