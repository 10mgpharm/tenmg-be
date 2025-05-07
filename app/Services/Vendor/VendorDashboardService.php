<?php

namespace App\Services\Vendor;

use App\Repositories\VendorDashboardRepository;
use Illuminate\Http\Request;

class VendorDashboardService
{

    public function __construct(private VendorDashboardRepository $vendorDashboardRepository)
    {
    }

    public function getDashboardStats(Request $request)
    {
        return $this->vendorDashboardRepository->getDashboardStats($request);
    }

    public function getGraphStats(Request $request)
    {
        return $this->vendorDashboardRepository->getGraphStats($request);
    }

}
