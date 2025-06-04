<?php

namespace App\Repositories;

use App\Models\Application;
use App\Models\EcommerceDiscount;
use App\Models\EcommerceOrder;
use App\Models\EcommercePayment;
use App\Models\EcommerceShopingList;
use App\Models\LoanApplication;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderRepository
{

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

    function getOrderByStatusCount()
    {
        try {

            $statuses = ['PENDING','PROCESSING','SHIPPED','DELIVERED','CANCELED','COMPLETED'];

            $query = EcommerceOrder::query();

            //check if user is not an admin
            $user = auth()->user();
            if (!$user->hasRole('admin')) {
                $businessId = $user->ownerBusinessType?->id ?: $user->businesses()
                        ->firstWhere('user_id', $user->id)?->id;
                // $query->where('business_id', $businessId);

                    $query->whereHas('orderDetails', function ($q) use ($businessId) {
                        $q->where('supplier_id', $businessId);
                    });
            }

            // Query orders, group by status, and count each one
            $counts = $query->select('status', DB::raw('COUNT(*) as total'))
            ->whereIn('status', $statuses) // Optional: filter only those statuses
            ->groupBy('status')
            ->get()
            ->pluck('total', 'status'); // Convert to an associative array

            // Create a default collection with all statuses and a count of zero
            $defaultCounts = collect($statuses)->flip()->map(fn() => 0);

            // Merge the database results with the default collection
            $result = $defaultCounts->merge($counts)->map(fn($total, $status) => [
            'status' => $status,
            'total' => $total,
            ])->values();

                return $result;

        } catch (\Throwable $th) {
            throw $th;
        }
    }

    function getOrderByStatusSuppliers(Request $request)
    {
        try {

            //check the auth user role
            $user = auth()->user();
            if ($user->hasRole('supplier')) {
                $businessId = $user->ownerBusinessType?->id ?: $user->businesses()
                        ->firstWhere('user_id', $user->id)?->id;
                $request->merge(['business_id' => $businessId]);
            }

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

            $businessId = $request->input('business_id');

            if (!empty($businessId)) {
                $query->whereHas('orderDetails', function ($q) use ($businessId) {
                    $q->where('supplier_id', $businessId);
                });
            }

            $query->where("status", '!=', 'CART')->orderBy("created_at", "desc");

            return $query->paginate(20);

        } catch (\Throwable $th) {
            throw $th;
        }
    }

    function getOrderDetails($id)
    {
        try {

            $orderDetails = EcommerceOrder::find($id);
            if (!$orderDetails) {
                throw new \Exception("Order not found", 404);
            }
            return $orderDetails;

        } catch (\Throwable $th) {
            throw $th;
        }
    }

    function getOrderDetailsSuppliers($id)
    {
        try {
            $user = Auth::user();
            $businessId = $user->ownerBusinessType?->id ?: $user->businesses()->firstWhere('user_id', $user->id)?->id;

            $orderDetails = EcommerceOrder::with(['orderDetails' => function($query) use ($businessId) {
                $query->where('supplier_id', $businessId);
            }])
            ->findOrFail($id);

            return $orderDetails;

        } catch (\Throwable $th) {
            throw $th;
        }
    }

    function getOrders(Request $request)
    {
        try {

            $query = EcommerceOrder::query();

            $status = $request->input('status');

            if (strtolower($request->input('status')) != "all") {
                $query->when(isset($status), function ($query) use ($status) {
                    $query->where("status",'like', "%{$status}%");
                });
            }

            $query->where("status", '!=', 'CART')->where("customer_id", Auth::id())->orderBy("created_at", "desc");

            return $query->paginate(20);

        } catch (\Throwable $th) {
            throw $th;
        }
    }

    function couponVerify(Request $request)
    {
        try {

            $cart = EcommerceOrder::where('status', "CART")->where('customer_id', Auth::id())->first();

            if (!$cart) {
                abort(400, 'You don\'t have item(s) in cart');
            }

            if($cart->discount_code == $request->coupon) {
                abort(400, "Coupon already applied to this order");
            }

            $coupon = $request->coupon;

            //get coupon details
            $foundCoupon = EcommerceDiscount::where('coupon_code', $coupon)->first();

            //check if coupon exist
            if (!$foundCoupon) {
                abort(400, "Coupon not found");
            }

            //check if coupon has expired
            if ($foundCoupon->status == "EXPIRED") {
                abort(400, "Coupon has expired");
            }

            // $startDate = Carbon::parse($foundCoupon->start_date);
            //check if coupon has started
            if ($foundCoupon->status == "INACTIVE") {
                abort(400, "Coupon has not started or is inactive");
            }

            $updatedOrder = $this->applyDiscountToOrder($cart, $foundCoupon);

            return $updatedOrder;

        } catch (\Throwable $th) {
            throw $th;

        }
    }

    function applyDiscountToOrder(EcommerceOrder $cart, EcommerceDiscount $couponData)
    {


        $minimumOrderAmount = $couponData->minimum_order_amount;
        $maximumDiscountAmount = $couponData->maximum_discount_amount;
        $couponType = $couponData->type;
        $applicableProduct = $couponData->applicable_products;

        if ($minimumOrderAmount != null && $cart->discount_price < $minimumOrderAmount) {
            abort(400, "Order amount is less than minimum order amount for discount");
        }


                foreach ($cart->orderDetails as $item) {

                    //check if this order belongs to the business that created the discount
                    if ($couponData->business_id != $item->supplier_id) {
                        continue;
                    }

                    // Skip if product is not eligible for discount
                    if ($applicableProduct !== null &&
                        !in_array($item->ecommerce_product_id, $applicableProduct)) {
                        continue;
                    }

                    // Calculate discount amount
                    $discountAmount = ($couponType === "PERCENT")
                        ? ($couponData->amount * $item->discount_price) / 100
                        : $couponData->amount;

                    // Apply maximum discount cap if specified
                    $amountToDeduct = isset($maximumDiscountAmount)
                        ? min($discountAmount, $maximumDiscountAmount)
                        : $discountAmount;

                    // Calculate new values
                    $newPrice = $item->discount_price - $amountToDeduct;
                    $tenmgCommission = ($item->tenmg_commission_percent * $newPrice) / 100;

                    // Update item
                    $item->update([
                        'discount_price' => $newPrice,
                        'tenmg_commission' => $tenmgCommission,
                        'discount_code' => $couponData->coupon_code,
                        'discount_type' => $couponData->type,
                        'discount_value' => $discountAmount, // discount amount ignoring max cap. To be used to reverse applied coupon when it expires before checkout.
                        'discount_expiration_date' => $couponData->end_date
                    ]);
                }


                $cart->order_total = $cart->orderDetails()->sum('discount_price');
                $cart->grand_total = $cart->orderDetails()->sum('discount_price');
                $cart->qty_total = $cart->orderDetails()->sum('quantity');
                $cart->save();

                return $cart;

    }

    function lastPaymentStatus()
    {
        $latestOrderPayment = EcommercePayment::where('customer_id', Auth::id())
            ->where('channel', 'tenmg_credit')
            ->orderBy('created_at', 'desc')
            ->first();
        $loanApplication = null;
        if ($latestOrderPayment) {
            $loanApplication = $loanApplication = LoanApplication::where('reference', $latestOrderPayment->reference)->first();
            if($loanApplication->status == "APPROVED"){
                $this->completeOrder($loanApplication);
            }
        }

        return [
            'transaction' => $latestOrderPayment,
            'application' =>$loanApplication
        ];
    }

    public function completeOrder($data)
    {
        // $body = $data->data;
        $merchantReference = $data->reference;
        // $status = $data->status;

        // if ($status != 'APPROVED') {
        //     throw new \Exception('Payment not successful');
        // }

        // //get the order payment instance
        $orderPayment = EcommercePayment::where('reference', $merchantReference)->first();

        if($orderPayment->status == 'success') {
            return $orderPayment;
        }

        // //update external reference
        $orderPayment->external_reference = $data->identifier;
        $orderPayment->status = 'success';
        $orderPayment->paid_at = now();
        $orderPayment->meta = json_encode($data);
        $orderPayment->save();

        //update order status
        $order = EcommerceOrder::find($orderPayment->order_id);
        $order->status = 'PENDING';
        $order->payment_status = "PAYMENT_SUCCESSFUL";
        $order->save();

        //send email to customer.
        $customer = User::find($order->customer_id);
        $customer->sendOrderConfirmationNotification('Your payment with id '.$merchantReference.' was successful. Your order is processing.', $customer);

        //send email to supplier
        $orderItems = $order->orderDetails;

        $this->removeBoughtItemFromShoppingList($orderItems);

        for ($i = 0; $i < count($orderItems); $i++) {
            $product = $orderItems[$i]->product;
            $supplier = $orderItems[$i]->supplier;
            $owner = $supplier->owner;

            //Reduce product quantity
            $product->quantity = $product->quantity - $orderItems[$i]->quantity;
            $product->save();

            $owner->sendOrderConfirmationNotification('You have a new order from '.$order->customer->name.'. Order for '.$product->name, $owner);
        }

        $admins = User::role('admin')->get();
        foreach ($admins as $admin) {
            $admin->sendOrderConfirmationNotification('New order from '.$order->customer->name, $admin);
        }

        return $orderPayment;

    }

    public function removeBoughtItemFromShoppingList($orderItems)
    {
        for ($i = 0; $i < count($orderItems); $i++) {
            EcommerceShopingList::where('user_id', Auth::id())->where('product_id', $orderItems[$i]->ecommerce_product_id)->delete();
        }
    }


}
