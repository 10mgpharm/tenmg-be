<?php

namespace App\Services\Storefront;

use App\Models\EcommerceOrder;
use App\Repositories\FincraPaymentRepository;
use App\Repositories\OrderRepository;
use Illuminate\Http\Request;

class EcommerceOrderService
{

    protected $orderRepository;
    protected $fincraPaymentRepository;

    function __construct(OrderRepository $orderRepository, FincraPaymentRepository $fincraPaymentRepository)
    {
        $this->orderRepository = $orderRepository;
        $this->fincraPaymentRepository = $fincraPaymentRepository;
    }

    function checkout(Request $request)
    {
        try {

            return $this->fincraPaymentRepository->initializePayment($request);

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

    function verifyFincraPayment($ref)
    {
        return $this->fincraPaymentRepository->verifyFincraPayment($ref);
    }

    function verifyFincraPaymentWebhook($ref)
    {
        return $this->fincraPaymentRepository->verifyFincraPaymentWebhook($ref);
    }

    function cancelPayment($ref)
    {
        return $this->fincraPaymentRepository->cancelPayment($ref);
    }
}
