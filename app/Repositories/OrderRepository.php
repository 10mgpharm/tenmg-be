<?php

namespace App\Repositories;

use App\Models\EcommerceOrder;
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

            // Query orders, group by status, and count each one:
            $counts = EcommerceOrder::select('status', DB::raw('COUNT(*) as total'))
                ->whereIn('status', $statuses) // optional filter only those statuses
                ->groupBy('status')
                ->get();

                return $counts;

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

}
