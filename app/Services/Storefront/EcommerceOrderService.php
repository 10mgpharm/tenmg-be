<?php

namespace App\Services\Storefront;

use App\Models\EcommerceOrder;
use App\Repositories\EcommercePaymentRepository;
use App\Repositories\FincraPaymentRepository;
use App\Repositories\OrderRepository;
use Illuminate\Http\Request;

class EcommerceOrderService
{

    protected $orderRepository;
    protected $fincraPaymentRepository;

    function __construct(OrderRepository $orderRepository, FincraPaymentRepository $fincraPaymentRepository, private EcommercePaymentRepository $ecommercePaymentRepository)
    {
        $this->orderRepository = $orderRepository;
        $this->fincraPaymentRepository = $fincraPaymentRepository;
    }

    function checkout(Request $request)
    {
        try {

            return $this->ecommercePaymentRepository->initializePayment($request);

        } catch (\Throwable $th) {
            throw $th;
        }
    }

    function getPaymentMethods()
    {
        return $this->ecommercePaymentRepository->getPaymentMethods();
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

    function couponVerify(Request $request)
    {
        return $this->orderRepository->couponVerify($request);
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

    function lastPaymentStatus()
    {
        return $this->orderRepository->lastPaymentStatus();

    }
}
