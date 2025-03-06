<?php

namespace App\Http\Controllers\API\Lender;

use App\Http\Controllers\Controller;
use App\Http\Resources\Lender\LenderDashboardResource;
use App\Models\Business;
use App\Services\Lender\LenderDashboardService;
use Illuminate\Http\Request;

class LenderDashboardController extends Controller
{

    function __construct(private LenderDashboardService $lenderDashboardService)
    {

    }

    public function getDashboardStats()
    {

        $business = $this->lenderDashboardService->getLenderDashboardData();

        return $this->returnJsonResponse(
            data: new LenderDashboardResource($business),
            message: 'Successful'
        );
    }

    public function getChartStats()
    {
        $stats = $this->lenderDashboardService->getChartStats();

        return $this->returnJsonResponse(
            data: $stats,
            message: 'Successful'
        );
    }

    public function initializeDeposit(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric'
        ]);

        $initData = $this->lenderDashboardService->initializeDeposit($request);
        return $this->returnJsonResponse(
            data: $initData,
            message: 'Successful'
        );
    }

}
