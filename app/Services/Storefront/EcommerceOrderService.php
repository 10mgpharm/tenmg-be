<?php

namespace App\Services\Storefront;

use App\Models\EcommerceOrder;
use Illuminate\Http\Request;

class EcommerceOrderService
{
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
}
