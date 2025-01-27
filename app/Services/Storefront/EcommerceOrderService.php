<?php

namespace App\Services\Storefront;

use App\Models\EcommerceOrder;
use App\Repositories\OrderPaymentRepository;
use App\Repositories\OrderRepository;
use Illuminate\Http\Request;

class EcommerceOrderService
{

    protected $orderRepository;
    protected $orderPaymentRepository;

    function __construct(OrderRepository $orderRepository, OrderPaymentRepository $orderPaymentRepository)
    {
        $this->orderRepository = $orderRepository;
        $this->orderPaymentRepository = $orderPaymentRepository;
    }

    function checkout(Request $request)
    {
        try {

            // $order = EcommerceOrder::find($request->orderId);
            // $order->status = 'PENDING';
            // $order->delivery_type = $request->deliveryType;
            // $order->delivery_address = $request->deliveryAddress;
            // $order->save();

            // return $order;
            return $this->orderPaymentRepository->initializePayment($request);

        } catch (\Throwable $th) {
            throw $th;
        }
    }

    function getOrderByStatusSuppliers(Request $request)
    {
        return $this->orderRepository->getOrderByStatusSuppliers($request);
    }

    function getOrderDetailsSuppliers($id)
    {
        return $this->orderRepository->getOrderDetailsSuppliers($id);
    }

    function getOrders(Request $request)
    {
        return $this->orderRepository->getOrders($request);
    }

    function getOrderDetails($id)
    {
        return $this->orderRepository->getOrderDetails($id);
    }
}
