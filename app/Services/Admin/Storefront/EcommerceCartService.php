<?php

namespace App\Services\Admin\Storefront;

use App\Models\EcommerceCart;
use App\Models\EcommerceOrder;
use App\Models\EcommerceOrderDetail;
use App\Models\EcommerceProduct;
use App\Settings\CreditSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EcommerceCartService
{

    public function __construct()
    {

    }

    function getOrderByStatus(Request $request)
    {
        try {

            $query = EcommerceOrder::query();

            $status = $request->input('status');
            $search = $request->input('search');

            if (strtolower($request->input('status')) != "all") {
                $query->when(isset($status), function ($query) use ($status) {
                    $query->where("status",'like', "%{$status}%");
                });
            }

            if (!empty($search)) {
                $query->whereHas('customer', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                });
            }

            $query->where("status", '!=', 'CART')->orderBy("created_at", "desc");

            return $query->paginate(20);

        } catch (\Throwable $th) {
            throw $th;
        }
    }

    function changeOrderStatus(Request $request)
    {
        try {

            $order = EcommerceOrder::find($request->input('orderId'));
            $order->status = $request->input('status');
            $order->refund_status = $request->input('refundStatus');
            $order->requires_refund = $request->input('requiresRefund');
            $order->save();

            return $order;

        } catch (\Throwable $th) {
            throw $th;
        }

    }

    function getOrderDetails($id)
    {
        try {

            $orderDetails = EcommerceOrder::find($id);
            return $orderDetails;

        } catch (\Throwable $th) {
            throw $th;
        }
    }


}
