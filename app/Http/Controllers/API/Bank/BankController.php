<?php

namespace App\Http\Controllers\API\Bank;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Services\AuthService;
use App\Services\Bank\BankService;
use Illuminate\Http\Request;

class BankController extends Controller
{
    private $bankService;

    private $authService;

    public function __construct(AuthService $authService, BankService $bankService)
    {
        $this->bankService = $bankService;
        $this->authService = $authService;
    }

    public function getBankList()
    {
        $banks = $this->bankService->getBankList();

        return $this->returnJsonResponse(
            message: 'Bank list successfully fetched.',
            data: $banks
        );
    }

    public function verifyBankAccount(Request $request)
    {
        $request->validate([
            'accountNumber' => 'required|digits:10',
            'bankCode' => 'required',
        ]);

        /**
         * DEV NOTE:
         * This is a mock response for the verifyBankAccount method
         * It is not intended to be used in production
         * It is only used to simulate the response from the bank service
         *
         * Fincra prod requires ip-whitelist
         * https://api.fincra.com/core/accounts/resolve
         *
         * Fincra sandbox often timeout using the sandbox base url
         * https://sandboxapi.fincra.com/core/accounts/resolve
         */
        if (config('app.env') != 'production') {
            return $this->returnJsonResponse(
                message: 'Bank account verified successfully.',
                data: [
                    'success' => true,
                    'message' => 'Account resolve successful.',
                    'data' => [
                        'accountNumber' => $request->accountNumber,
                        'accountName' => 'Mart Olumide',
                    ],
                ]
            );
        }

        $bankDetails = $this->bankService->verifyBankAccount($request);

        return $this->returnJsonResponse(
            message: 'Bank account verified successfully.',
            data: $bankDetails
        );
    }

    public function store(Request $request)
    {
        $request->validate([
            'identifier' => 'required|exists:credit_customers,identifier',
            'accountNumber' => 'required|digits:10',
            'accountName' => 'required|string',
            'bankCode' => 'required',
            'bankName' => 'required',
            'isDefault' => 'sometimes|nullable|bool',
        ]);

        $requestData = $request->only([
            'identifier',
            'accountNumber',
            'accountName',
            'bankCode',
            'bankName',
            'isDefault',
        ]);

        $businessId = $this->authService->getBusiness()?->id;

        if (! $businessId) {
            return $this->returnJsonResponse(
                message: 'Business not found.',
                data: null,
                status: 404
            );
        }

        if ($businessId) {
            $requestData['businessId'] = $businessId;
        }

        $bank = $this->bankService->createBank($requestData);

        return $this->returnJsonResponse(
            message: 'Bank account created successfully.',
            data: $bank
        );
    }

    public function getDefaultBank(Customer $customer)
    {
        $businessId = $this->authService->getBusiness()?->id;

        if (! $businessId) {
            return $this->returnJsonResponse(
                message: 'Business not found.',
                data: null,
                status: 404
            );
        }

        $bank = $this->bankService->getDefaultBank($businessId, $customer);

        return $this->returnJsonResponse(
            message: 'Default bank fetched successfully.',
            data: $bank
        );
    }
}
