<?php

namespace App\Repositories;

use App\Models\EcommerceOrder;
use Illuminate\Http\Request;

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
            return $orderDetails;

        } catch (\Throwable $th) {
            throw $th;
        }
    }

}
