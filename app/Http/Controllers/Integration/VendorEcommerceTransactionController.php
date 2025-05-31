<?php

namespace App\Http\Controllers\Integration;

use App\Http\Controllers\Controller;
use App\Http\Requests\Integration\SearchVendorEcommerceTransactionRequest;
use App\Models\EcommerceOrder;
use App\Models\EcommerceTransaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class VendorEcommerceTransactionController extends Controller
{
    /**
     * This controller is intended to handle vendor ecommerce transactions.
     * for credit scoring.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(SearchVendorEcommerceTransactionRequest $request)
    {
        $validated = $request->validated();

        $result = EcommerceOrder::query()
            ->where('status', 'completed')
            ->whereHas('customer.roles', function ($query) use ($validated) {
                $query->where('name', 'customer')
                    ->where('email', $validated['email']);
            })
            ->whereBetween('created_at', [now()->subMonths(6), now()])
            ->get();

        return response()->json([
                    'message' => 'Ecommerce transactions successfully fetched.',
                    'data' => $result->map(function ($order) {
                        return [
                            'identifier' => $order->identifier,
                            'customer_name' => $order->customer->name,
                            'customer_email' => $order->customer->email,
                            'total_amount' => $order->order_total,
                            'status' => $order->status,
                            'created_at' => $order->created_at->toDateTimeString(),
                        ];
                    }),
                ], Response::HTTP_OK);
    }
}
