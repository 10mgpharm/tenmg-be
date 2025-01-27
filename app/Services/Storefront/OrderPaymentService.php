<?php

namespace App\Services\Storefront;

use App\Repositories\OrderPaymentRepository;
use Illuminate\Http\Request;

class OrderPaymentService
{

    protected $orderPaymentRepository;

    function __construct(OrderPaymentRepository $orderPaymentRepository)
    {
        $this->orderPaymentRepository = $orderPaymentRepository;
    }

    function initializePayment(Request $request)
    {
        try {

            return $this->orderPaymentRepository->initializePayment($request);

        } catch (\Throwable $th) {
            throw $th;
        }
    }

}
