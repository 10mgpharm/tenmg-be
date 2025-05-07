<?php

namespace App\Http\Controllers\API\Vendor;

use App\Http\Controllers\Controller;
use App\Services\Vendor\VendorDashboardService;
use Illuminate\Http\Request;

class VendorDashboardController extends Controller
{
    public function __construct(private VendorDashboardService $vendorDashboardService) {}

    public function getDashboardStats(Request $request)
    {
        $dashboardStats = $this->vendorDashboardService->getDashboardStats($request);

        return $this->returnJsonResponse(
            data: $dashboardStats
        );
    }

    public function getGraphStats(Request $request)
    {
        $graphStats = $this->vendorDashboardService->getGraphStats($request);

        return $this->returnJsonResponse(
            data: $graphStats
        );
    }


}
