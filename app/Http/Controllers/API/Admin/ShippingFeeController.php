<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreShippingFeeRequest;
use App\Http\Resources\Admin\ShippingFeeResource;
use App\Models\ShippingFee;
use Illuminate\Http\Request;

class ShippingFeeController extends Controller
{
    //
    public function index(Request $request)
    {

        $shipping_fee = ShippingFee::first();
        
        return $this->returnJsonResponse(
            message: 'Shipping fee successfully fetched.',
            data: $shipping_fee ? new ShippingFeeResource($shipping_fee) : null,
        );
    }

    public function store(StoreShippingFeeRequest $request)
    {
        $validated = $request->validated();

        $shipping_fee = ShippingFee::first();

        if ($shipping_fee) {
            $shipping_fee->update($validated);
        } else {
            $shipping_fee = ShippingFee::create($validated);
        }

        return $this->returnJsonResponse(
            message: 'Shipping fee successfully saved.',
            data: new ShippingFeeResource($shipping_fee)
        );
    }

    public function update(StoreShippingFeeRequest $request)
    {

        $validated = $request->validated();

        $shipping_fee = ShippingFee::first();

        if ($shipping_fee) {
            $shipping_fee->update($validated);
        } else {
            $shipping_fee = ShippingFee::create($validated);
        }

        return $this->returnJsonResponse(
            message: 'Shipping fee successfully updated.',
            data: new ShippingFeeResource($shipping_fee)
        );
    }
}
