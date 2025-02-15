<?php

namespace App\Http\Controllers\API\Bank;

use App\Http\Controllers\Controller;
use App\Services\Bank\BankService;
use Illuminate\Http\Request;

class BankController extends Controller
{
    private $bankService;

    public function __construct(BankService $bankService)
    {
        $this->bankService = $bankService;
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
}
