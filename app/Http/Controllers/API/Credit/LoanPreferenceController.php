<?php

namespace App\Http\Controllers\API\Credit;

use App\Http\Controllers\Controller;
use App\Models\TenmgCreditRequest;
use App\Services\Credit\LoanPreferenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class LoanPreferenceController extends Controller
{
    public function __construct(
        private LoanPreferenceService $loanPreferenceService
    ) {}

    /**
     * Simulate repayment plan for a purchase.
     *
     * Request body:
     * - amount (required, numeric)
     * - tenor (optional, numeric) - 1, 2, 3, or 4 months
     *   If omitted or invalid, the system defaults to 1 month.
     * - borrower_reference (required, string) - unique identifier for the vendor's customer
     * - currency (optional, string) - default NGN
     * - transaction_history (optional, array) - optional transaction history data
     * - product_items (optional, array) - optional product items data
     * - callback_url (optional, url) - callback URL for notifications
     *
     * Note: interest rate is determined internally per tenor (loan preference config).
     */
    public function simulate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1000',
            'tenor' => 'nullable|numeric|in:1,2,3,4',
            // We handle uniqueness at the DB + service layer (update-or-create by borrower_reference)
            'borrower_reference' => 'required|string|max:255',
            'businessname' => 'nullable|string|max:255',
            'currency' => 'nullable|string|size:3',
            'transaction_history' => 'nullable|array',
            'product_items' => 'nullable|array',
            'callback_url' => 'nullable|url',
        ]);

        // When called via clientAuth, the middleware attaches the vendor Business model
        // into the request as 'business'. We forward it to the service via the payload
        // so it can create/update LenderMatch records even without an authenticated user.
        $businessFromClient = $request->input('business');
        if ($businessFromClient) {
            $validated['business'] = $businessFromClient;
        }

        $amount = (int) $validated['amount'];
        $tenor = isset($validated['tenor']) ? (int) $validated['tenor'] : null;

        $result = $this->loanPreferenceService->simulate($amount, $tenor, $validated);

        return $this->returnJsonResponse(
            data: $result,
            message: 'Repayment plan calculated successfully'
        );
    }

    /**
     * Match lender with vendor customer.
     * Supports two modes:
     * 1. With full payload (same as simulate)
     * 2. With request_id (fetches stored payload from Tenmg credit request)
     *
     * Request body options:
     * Option 1 - Full payload:
     * - amount (required, numeric)
     * - tenor (optional, numeric) - 1, 2, 3, or 4 months
     *   If omitted or invalid, the system defaults to 1 month.
     * - borrower_reference (required, string) - unique identifier for the vendor's customer
     * - currency (optional, string) - default NGN
     * - transaction_history (optional, array)
     * - product_items (optional, array)
     * - callback_url (optional, url)
     *
     * Option 2 - With request_id:
     * - request_id (required, string) - Tenmg credit request ID
     * - Any additional fields to override stored payload (optional)
     */
    public function match(Request $request): JsonResponse
    {
        $requestId = $request->input('request_id');

        // If request_id is provided, fetch stored payload
        if ($requestId) {
            $tenmgRequest = TenmgCreditRequest::where('request_id', $requestId)->first();

            if (! $tenmgRequest) {
                return $this->returnJsonResponse(
                    message: 'Tenmg credit request not found',
                    data: null,
                    status: 'failed',
                    statusCode: Response::HTTP_NOT_FOUND
                );
            }

            // Merge stored payload with any additional request data (allows override)
            $payload = array_merge($tenmgRequest->payload ?? [], $request->except('request_id'));
        } else {
            // Use request payload directly (existing behavior)
            $payload = $request->all();
        }

        // Normalize tenor (allow legacy default_tenor but prefer tenor)
        if (! isset($payload['tenor']) && isset($payload['default_tenor'])) {
            $payload['tenor'] = $payload['default_tenor'];
        }

        // Validate the payload
        $validated = validator($payload, [
            'amount' => 'required|numeric|min:1000',
            'tenor' => 'nullable|numeric|in:1,2,3,4',
            'borrower_reference' => 'required|string|max:255',
            'businessname' => 'nullable|string|max:255',
            'currency' => 'nullable|string|size:3',
            'transaction_history' => 'nullable|array',
            'product_items' => 'nullable|array',
            'callback_url' => 'required|url',
        ])->validate();

        // When called via clientAuth, the middleware attaches the vendor Business model
        // into the request as 'business'. We forward it to the service via the payload
        // so it can create/update LenderMatch records even without an authenticated user.
        $businessFromClient = $request->input('business');
        if ($businessFromClient) {
            $validated['business'] = $businessFromClient;
        }

        $amount = (int) $validated['amount'];
        $tenor = isset($validated['tenor']) ? (int) $validated['tenor'] : null;

        $result = $this->loanPreferenceService->simulate($amount, $tenor, $validated);

        return $this->returnJsonResponse(
            data: array_merge($result, [
                'borrower_reference' => $validated['borrower_reference'],
                'callback_url' => $validated['callback_url'],
            ]),
            message: 'Lender matched successfully'
        );
    }
}
