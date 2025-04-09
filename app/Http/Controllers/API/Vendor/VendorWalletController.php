<?php

namespace App\Http\Controllers\API\Vendor;

use App\Http\Controllers\Controller;
use App\Http\Resources\CreditTransactionHistoryResource;
use App\Services\Vendor\VendorWalletService;
use Illuminate\Http\Request;

class VendorWalletController extends Controller
{
    public function __construct(private VendorWalletService $vendorWalletService)
    {

    }

    public function getWalletStats()
    {
        return $this->vendorWalletService->getWalletStats();
    }

    public function getTransactions()
    {
        $transactionList = $this->vendorWalletService->getTransactions();

        return $this->returnJsonResponse(
            data: CreditTransactionHistoryResource::collection($transactionList)->response()->getData(true)
        );
    }


}
