<?php

namespace App\Http\Controllers\API\Credit;

use App\Http\Controllers\Controller;
use App\Http\Resources\LoanResource;
use App\Services\RepaymentProcessingService;
use Illuminate\Http\Request;

class LoanRepaymentController extends Controller
{
    function __construct(private RepaymentProcessingService $repaymentProcessingService)
    {
    }

    public function verifyRepaymentLink($reference)
    {
        $response = $this->repaymentProcessingService->verifyRepaymentLink($reference);

        return $this->returnJsonResponse(
            message: 'Repayment link verified successfully',
            data: new LoanResource($response)
        );
    }

    public function makeRepayment(Request $request)
    {
        $request->validate([
            'reference' => 'required|string|exists:credit_loans,identifier'
        ]);

        $response = $this->repaymentProcessingService->makeRepayment($request);

        return $this->returnJsonResponse(
            message: 'Repayment processed successfully',
            data: $response
        );

    }


}
