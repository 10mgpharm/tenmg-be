<?php

namespace App\Http\Controllers\API\Storefront;

use App\Http\Controllers\Controller;
use App\Services\Storefront\OrderPaymentService;
use Illuminate\Http\Request;

class OrderPaymentController extends Controller
{

    protected $orderPaymentService;

    function __construct(OrderPaymentService $orderPaymentService)
    {
        $this->orderPaymentService = $orderPaymentService;
    }

    function initializePayment(Request $request)
    {
        $paymentData = $this->orderPaymentService->initializePayment($request);

        return $this->returnJsonResponse(
            message: 'Products successfully fetched.',
            data: $paymentData
        );
    }

}
