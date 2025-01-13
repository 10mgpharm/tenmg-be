<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\EcommerceCartResource;
use App\Services\Admin\Storefront\EcommerceCartService;
use Exception;
use Illuminate\Http\Request;

class EcommerceOrderController extends Controller
{

    protected $ecommerceCartService;

    function __construct(EcommerceCartService $ecommerceCartService)
    {
        $this->ecommerceCartService = $ecommerceCartService;
    }

    function getOrderByStatus(Request $request)
    {
        $validStatuses = ['PENDING','CONFIRMED','SHIPPED','DELIVERED','CANCELED','CART','COMPLETED'];

        $orders = $this->ecommerceCartService->getOrderByStatus($request);

        return $this->returnJsonResponse(
            data: EcommerceCartResource::collection($orders)->response()->getData(true)
        );
    }

    function changeOrderStatus(Request $request)
    {
        $request->validate([
            'orderId' => 'required|exists:ecommerce_orders,id',
            'status' => 'required|in:PENDING,CONFIRMED,SHIPPED,DELIVERED,CANCELED,COMPLETED',
            'reason' => 'required_if:status,CANCELED|string',
            'requiresRefund' => 'required_if:status,CANCELED|boolean',
            'refundStatus' => 'required_if:status,CANCELED||in:REFUNDED,AWAITING REFUND'
        ]);

        $this->ecommerceCartService->changeOrderStatus($request);

        return $this->returnJsonResponse(message: 'Order status updated');

    }
}
