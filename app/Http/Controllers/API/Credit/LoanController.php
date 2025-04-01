<?php

namespace App\Http\Controllers\API\Credit;

use App\Http\Controllers\Controller;
use App\Http\Resources\LoanResource;
use App\Services\LoanService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LoanController extends Controller
{
    public function __construct(private LoanService $loanService)
    {
    }

    /**
     * Get all loans.
     */
    public function getAllLoans(): JsonResponse
    {
        $loans = $this->loanService->getAllLoans();

        return $this->returnJsonResponse(data: $loans);
    }

    /**
     * Get all loans resourced.
     */
    public function getLoanList(Request $request): JsonResponse
    {
        $loans = $this->loanService->getLoanList($request->all(), $request->perPage ?? 10);

        return $this->returnJsonResponse(data: LoanResource::collection($loans)->response()->getData(true));
    }

    public function getLoanDetails(int $id): JsonResponse
    {
        $loan = $this->loanService->getLoanById($id);
        if (!$loan) {
            throw new Exception('Loan not found', 404);
        }

        return $this->returnJsonResponse(data: new LoanResource($loan));
    }

    /**
     * Get loan by ID.
     */
    public function getLoanById(int $id): JsonResponse
    {
        $loan = $this->loanService->getLoanById($id);
        if (!$loan) {
            throw new Exception('Loan not found', 404);
        }

        return $this->returnJsonResponse(data: $loan);
    }

    /**
     * Mark loan as disbursed.
     */
    public function disbursed(int $id): JsonResponse
    {
        $disbursed = $this->loanService->markAsDisbursed($id);
        if (!$disbursed) {
            throw new Exception('Failed to disburse loan', 400);
        }

        return $this->returnJsonResponse(message: 'Loan disbursed successfully', data: $disbursed);
    }

    public function liquidateLoan(int $repaymentScheduleId): JsonResponse
    {
        $response = $this->loanService->processLoanRepayment(repaymentScheduleId: $repaymentScheduleId, isLiquidation: true);
        return $this->returnJsonResponse(message: 'Loan liquidation is being process', data: $response);
    }

    public function repayLoan(int $repaymentScheduleId): JsonResponse
    {
        $response = $this->loanService->processLoanRepayment(repaymentScheduleId: $repaymentScheduleId);
        return $this->returnJsonResponse(message: 'Loan repayment is being process', data: $response);
    }

    public function getLoanStats()
    {
        $stats = $this->loanService->getLoanStats();

        return $this->returnJsonResponse(data: $stats);

    }

}
