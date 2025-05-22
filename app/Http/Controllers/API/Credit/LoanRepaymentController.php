<?php

namespace App\Http\Controllers\API\Credit;

use App\Http\Controllers\Controller;
use App\Http\Resources\CreditRepaymentResource;
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

    public function initiateRepayment(Request $request)
    {
        $request->validate([
            'reference' => 'required|string|exists:credit_loans,identifier',
            'paymentType' => 'required|string|in:fullPayment,partPayment',
            'noOfMonths' => 'required|numeric|min:0',
        ]);

        $response = $this->repaymentProcessingService->initiateRepayment($request);

        return $this->returnJsonResponse(
            message: 'Repayment processed successfully',
            data: $response
        );

    }

    public function sentTestRepayPaymentMail($loanRef)
    {

        $response = $this->repaymentProcessingService->sendRemindersTest($loanRef);

        return $this->returnJsonResponse(
            message: 'Repayment mail sent',
            data: $response
        );
    }

    public function cancelPayment($paymentRef)
    {
        $response = $this->repaymentProcessingService->cancelPayment($paymentRef);

        return $this->returnJsonResponse(
            message: 'Payment cancelled successfully',
            data: $response
        );
    }

    function verifyFincraPayment($ref)
    {
        return $this->repaymentProcessingService->verifyFincraPayment($ref);
    }

    public function getListOfLoanRepayments(Request $request)
    {
        $repayment = $this->repaymentProcessingService->getListOfLoanRepayments($request->all(), $request->perPage ?? 10);

        return $this->returnJsonResponse(
            data: CreditRepaymentResource::collection($repayment)->response()->getData(true)
        );
    }


}
