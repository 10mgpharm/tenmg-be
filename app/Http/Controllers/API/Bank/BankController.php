<?php

namespace App\Http\Controllers\API\Bank;

use App\Http\Controllers\Controller;
use App\Services\Bank\BankService;
use Illuminate\Http\Request;

class BankController extends Controller
{

    private $bankService;

    function __construct(BankService $bankService)
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
            'bankCode' => 'required'
        ]);

        $bankDetails = $this->bankService->verifyBankAccount($request);
        return $this->returnJsonResponse(
            message: 'Bank account verified successfully.',
            data: $bankDetails
        );
    }

}
