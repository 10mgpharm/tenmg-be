<?php

namespace App\Http\Controllers\API\Lender;

use App\Exports\TransactionExport;
use App\Exports\UserDataExport;
use App\Http\Controllers\Controller;
use App\Http\Resources\Lender\LenderDashboardResource;
use App\Mail\StatementEmail;
use App\Models\Business;
use App\Models\CreditTransactionHistory;
use App\Services\Lender\LenderDashboardService;
use Illuminate\Container\Attributes\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Maatwebsite\Excel\Facades\Excel;

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

    public function cancelDepositPayment(Request $request, $ref)
    {
        $this->lenderDashboardService->cancelDepositPayment($ref);
        return $this->returnJsonResponse(
            message: 'Successful'
        );

    }

    public function generateStatement(Request $request)
    {
        $request->validate([
            'dateFrom' => 'required|date',
            'dateTo' => 'required|date'
        ]);

        $fileName = "statement-".now()->format('Y-m-d');

        $transaction = $this->lenderDashboardService->generateStatement($request);

        // Generate the Excel file and store it temporarily
        $excelFile = Excel::raw(new TransactionExport($transaction, $this), \Maatwebsite\Excel\Excel::XLSX);

        $user = $request->user();

        // Send email with attachment
        Mail::to("uhweka@gmail.com") // or use a specific email address
            ->send(new StatementEmail($excelFile, $fileName));


        return Excel::download(new TransactionExport($transaction, $this), "{$fileName}.xlsx");

    }

    public function withdrawFunds(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric',
            'bankName' => 'required',
            'accountNumber' => 'required',
            'accountName' => 'required',
            'bankCode' => 'required'
        ]);

        $withdraw = $this->lenderDashboardService->withdrawFunds($request);
        return $this->returnJsonResponse(
            data: $withdraw,
            message: 'Successful'
        );
    }

}
