<?php

namespace App\Http\Controllers\API\Credit;

use App\Http\Controllers\Controller;
use App\Services\LoanService;
use Exception;
use Illuminate\Http\JsonResponse;

class LoanController extends Controller
{
    public function __construct(private LoanService $loanService) {}

    /**
     * Get all loans.
     */
    public function getAllLoans(): JsonResponse
    {
        $loans = $this->loanService->getAllLoans();

        return $this->returnJsonResponse(data: $loans);
    }

    /**
     * Get loan by ID.
     */
    public function getLoanById(int $id): JsonResponse
    {
        $loan = $this->loanService->getLoanById($id);
        if (! $loan) {
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
        if (! $disbursed) {
            throw new Exception('Failed to disburse loan', 400);
        }

        return $this->returnJsonResponse(message: 'Loan disbursed successfully');
    }
}
