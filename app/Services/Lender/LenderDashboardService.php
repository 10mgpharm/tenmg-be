<?php

namespace App\Services\Lender;


use App\Repositories\LenderDashboardRepository;
use Illuminate\Http\Request;

class LenderDashboardService
{

    function __construct(private LenderDashboardRepository $lenderDashboardRepository)
    {

    }

    public function getLenderDashboardData()
    {
        return $this->lenderDashboardRepository->getDashboardStats();
    }

    public function getChartStats()
    {
        return $this->lenderDashboardRepository->getChartStats();
    }

    public function initializeDeposit(Request $request)
    {
        return $this->lenderDashboardRepository->initializeDeposit($request);
    }

}
