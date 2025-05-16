<?php

namespace App\Repositories;

use App\Models\EcommerceDiscount;
use App\Models\EcommerceOrder;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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

            $user = Auth::user();
            $businessId = $user->ownerBusinessType?->id ?: $user->businesses()->firstWhere('user_id', $user->id)?->id;

            $cart = EcommerceOrder::where('status', "CART")->where('business_id', $businessId)->first();

            if (!$cart) {
                throw "No open cart";
            }

            $coupon = $request->coupon;

            //get coupon details
            $foundCoupon = EcommerceDiscount::where('coupon_code', $coupon)->first();

            //check if coupon exist
            if (!$foundCoupon) {
                throw "Coupon not found";
            }

            //check if coupon has expired
            if ($foundCoupon->status == "EXPIRED") {
                throw "Coupon has expired";
            }

            $startDate = Carbon::parse($foundCoupon->start_date);
            //check if coupon has started
            if ($startDate->isPast() || $startDate->isToday()) {
                throw "Coupon has not started";
            }



            $couponValue = $foundCoupon->amount;
            $shouldBeApplyToAllProducts = $foundCoupon->all_products;
            $applicableProduct = $foundCoupon->applicable_products;
            $status = $foundCoupon->status;
            $minimumOrderAmount = $foundCoupon->minimum_order_amount;
            $maximumDiscountAmount = $foundCoupon->maximum_discount_amount;
            $couponForBusiness = $foundCoupon->business_id;





        } catch (\Throwable $th) {
            throw $th;

        }
    }

    function applyToAllProducts(EcommerceOrder $cart, EcommerceDiscount $couponData)
    {



        $minimumOrderAmount = $couponData->minimum_order_amount;
        $maximumDiscountAmount = $couponData->maximum_discount_amount;

        if($minimumOrderAmount == null && $maximumDiscountAmount == null){

            $updatedCartItems = [];

            $couponType = $couponData->type;

            //loop through the cart items
            foreach ($cart->orderDetails as $cartItem) {
                $amountToDeduct = 0;
                if($couponType == "FIXED"){
                    $amountToDeduct = $couponData->amount;
                }else{
                    $amountToDeduct = ($cartItem->discount_amount * $couponData->amount) / 100;
                }
                $newDiscount = $couponData->discount_amount - $amountToDeduct;
                $cartItem->discount_amount = $newDiscount;
                $cartItem->tenmg_commission = ($cartItem->tenmg_commission * $newDiscount)/100;

                $updatedCartItems[] = $cartItem;
            }

        }

    }

}
