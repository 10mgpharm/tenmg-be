<?php

namespace App\Http\Controllers\API\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Resources\EcommerceCartResource;
use App\Services\Storefront\EcommerceOrderService;
use Illuminate\Http\Request;

class OrdersController extends Controller
{
    protected $ecommerceOrderService;

    function __construct(EcommerceOrderService $ecommerceOrderService)
    {
        $this->ecommerceOrderService = $ecommerceOrderService;
    }

    function checkout(Request $request)
    {
        $request->validate([
            'orderId' => 'required|exists:ecommerce_orders,id',
            // 'paymentMethodId' => 'required|exists:ecommerce_payment_methods,id',
            'deliveryAddress' => 'required|string',
            'deliveryType' => 'required|:STANDARD,EXPRESS'
        ]);
        $order = $this->ecommerceOrderService->checkout($request);

        return $this->returnJsonResponse(message: 'Success', data: new EcommerceCartResource($order));
    }
}
