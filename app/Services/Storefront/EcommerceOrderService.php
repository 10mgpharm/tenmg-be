<?php

namespace App\Services\Storefront;

use App\Models\EcommerceOrder;
use App\Repositories\OrderRepository;
use Illuminate\Http\Request;

class EcommerceOrderService
{

    protected $orderRepository;

    function __construct(OrderRepository $orderRepository)
    {
        $this->orderRepository = $orderRepository;
    }

    function checkout(Request $request)
    {
        try {

            $order = EcommerceOrder::find($request->orderId);
            $order->status = 'PENDING';
            $order->delivery_type = $request->deliveryType;
            $order->delivery_address = $request->deliveryAddress;
            $order->save();

            return $order;

        } catch (\Throwable $th) {
            throw $th;
        }
    }

    function getOrderByStatusSuppliers(Request $request)
    {
        return $this->orderRepository->getOrderByStatusSuppliers($request);
    }
}
