<?php

namespace App\Services\Storefront;

use App\Repositories\FincraPaymentRepository;
use Illuminate\Http\Request;

class OrderPaymentService
{

    protected $fincraPaymentRepository;

    function __construct(FincraPaymentRepository $fincraPaymentRepository)
    {
        $this->fincraPaymentRepository = $fincraPaymentRepository;
    }

    function initializePayment(Request $request)
    {
        try {

            return $this->fincraPaymentRepository->initializePayment($request);

        } catch (\Throwable $th) {
            throw $th;
        }
    }

}
