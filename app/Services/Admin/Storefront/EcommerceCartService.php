<?php

namespace App\Services\Admin\Storefront;

use App\Models\EcommerceCart;
use App\Models\EcommerceOrder;
use App\Models\EcommerceOrderDetail;
use App\Models\EcommerceProduct;
use App\Models\EcommerceWallet;
use App\Repositories\OrderRepository;
use App\Services\SupplierOrderWalletService;
use App\Settings\CreditSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EcommerceCartService
{
    protected $orderRepository;

    public function __construct(OrderRepository $orderRepository)
    {
        $this->orderRepository = $orderRepository;
    }

    function getOrderByStatus(Request $request)
    {
        return $this->orderRepository->getOrderByStatus($request);
    }

    function getOrderByStatusCount()
    {
        return $this->orderRepository->getOrderByStatusCount();
    }

    function changeOrderStatus(Request $request)
    {
        try {

            $order = EcommerceOrder::find($request->input('orderId'));
            $order->status = $request->input('status');
            $order->refund_status = $request->input('refundStatus');
            $order->requires_refund = $request->input('requiresRefund');
            $order->save();


            
            switch ($order->status) {
                case 'COMPLETED':
                    // If the order is completed, credit the supplier(s)
                    (new SupplierOrderWalletService)->credit($order);
                    break;

                case 'CANCELED':
                    // If the order is canceled, we need to debit the supplier(s)
                    (new SupplierOrderWalletService)->debit($order);
                    break;
            }

            
            return $order;

        } catch (\Throwable $th) {
            throw $th;
        }

    }

    function getOrderDetails($id)
    {
        return $this->orderRepository->getOrderDetails($id);
    }


}
