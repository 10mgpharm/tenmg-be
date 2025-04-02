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

    public function cancelDepositPayment($ref)
    {
        return $this->lenderDashboardRepository->cancelDepositPayment($ref);
    }

    public function generateStatement(Request $request): \Illuminate\Database\Eloquent\Builder{
        return $this->lenderDashboardRepository->generateStatement($request);
    }

    public function withdrawFunds(Request $request)
    {
        return $this->lenderDashboardRepository->withdrawFunds($request);
    }

    public function transferToDepositWallet(Request $request)
    {
        return $this->lenderDashboardRepository->transferToDepositWallet($request);
    }

}
