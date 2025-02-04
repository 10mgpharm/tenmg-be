<?php

namespace App\Http\Controllers\API\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Resources\EcommerceCartResource;
use App\Models\EcommerceOrder;
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

        return $this->returnJsonResponse(message: 'Success', data: $order);
    }

    function getOrders(Request $request)
    {
        $order = $this->ecommerceOrderService->getOrders($request);
        return $this->returnJsonResponse(message: 'Success', data: EcommerceCartResource::collection($order));
    }

    function getOrderDetails($id)
    {
        $order = $this->ecommerceOrderService->getOrderDetails($id);
        return $this->returnJsonResponse(message: 'Success', data: new EcommerceCartResource($order));
    }

    function verifyFincraPayment($ref)
    {
        $orderPayment = $this->ecommerceOrderService->verifyFincraPayment($ref);
        return $this->returnJsonResponse(message: 'Success', data: $orderPayment);
    }

    function cancelPayment($ref)
    {
        $this->ecommerceOrderService->cancelPayment($ref);
        return $this->returnJsonResponse(message: 'Success');
    }
}
