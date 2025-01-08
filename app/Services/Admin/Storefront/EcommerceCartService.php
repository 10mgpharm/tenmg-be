<?php

namespace App\Services\Admin\Storefront;

use App\Models\EcommerceCart;
use App\Models\EcommerceProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EcommerceCartService
{

    public function __construct()
    {

    }

    function addRemoveItemToCart(Request $request)
    {

        try {

            DB::beginTransaction();

            //check if user has an active cart
            $cart = EcommerceCart::where('customer_id', Auth::id())->where('status', 'active')->first();
            if ($cart) {
                $product = EcommerceProduct::find($request->productId);
                //check if item already exists in cart
                $item = $cart->items()->where('product_id', $request->productId)->first();

                if ($item) {

                    //check if the action is remove
                    if($request->action == "remove"){
                        $item->delete();
                        DB::commit();
                    }else{
                        //check if quantity to minus is greater than what is is cart
                        if ($request->action == "minus" && $item->qty <= $request->qty) {
                            throw new \Exception("Quantity to minus is greater than what is in cart");
                        }
                        $qty = $request->action == "add" ? (int)$item->qty + (int)$request->qty : abs((int)$item->qty - (int)$request->qty);
                        //update item quantity
                        $item->qty = $qty;
                        $item->total_price = (float)$product->discount_price * $qty;
                        $item->unit_price = $product->discount_price;
                        $item->save();
                    }


                } else {

                    $product = EcommerceProduct::find($request->productId);
                    //add item to cart
                    $cart->items()->create([
                        'product_id' => $request->productId,
                        'qty' => $request->qty,
                        'unit_price' => $product->discount_price,
                        'total_price' => $product->discount_price * $request->qty,
                    ]);
                }

                //update cart total
                $cart->total_price = $cart->items()->sum('total_price');
                $cart->save();

            } else {
                $product = EcommerceProduct::find($request->productId);
                //create new cart
                $cart = EcommerceCart::create([
                    'customer_id' => Auth::id(),
                    'status' => 'active',
                    'total_price' => $product->discount_price * $request->qty
                ]);

                //add item to cart
                $cart->items()->create([
                    'product_id' => $request->productId,
                    'qty' => $request->qty,
                    'unit_price' => $product->discount_price,
                    'total_price' => $product->discount_price * $request->qty,
                ]);
            }

            DB::commit();

            return $cart;

        } catch (\Throwable $th) {
            //throw $th;
            DB::rollBack();
            throw $th;
        }

    }

    function getUserCart()
    {
        try {
            $cart = EcommerceCart::where('customer_id', Auth::id())->where('status', 'active')->get();
            return $cart;
        } catch (\Throwable $th) {
            throw $th;
        }
    }


}
