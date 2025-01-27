<?php

namespace App\Services\Storefront;

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

    function addRemoveItemToCart(Request $request)
    {

        try {

            DB::beginTransaction();

            $creditSettings = new CreditSettings();
            $tenmgPercent = $creditSettings->tenmg_ecommerce_commission_percent;

            //check if user has an active cart
            $cart = EcommerceOrder::where('customer_id', Auth::id())->where('status', 'CART')->first();
            if ($cart) {
                $product = EcommerceProduct::find($request->productId);
                //check if item already exists in cart
                $item = $cart->orderDetails()->where('ecommerce_product_id', $request->productId)->first();

                if ($item) {

                    //check if the action is remove
                    if($request->action == "remove"){
                        $item->delete();
                        DB::commit();
                    }else{
                        //check if quantity to minus is greater than what is is cart
                        if ($request->action == "minus" && $item->quantity <= $request->qty) {
                            throw new \Exception("Quantity to minus is greater than what is in cart");
                        }
                        $orderPrice = $product->actual_price - $product->discount_price;
                        $totalWeight = $request->qty * $product->weight;
                        $tenmgPercentCommission = ($tenmgPercent/100) * $orderPrice;

                        $qty = $request->action == "add" ? (int)$item->quantity + (int)$request->qty : abs((int)$item->quantity - (int)$request->qty);
                        //update item quantity
                        $item->quantity = $qty;
                        $item->actual_price = $product->actual_price * $request->qty;
                        $item->discount_price = $orderPrice * $request->qty;
                        $item->tenmg_commission = $tenmgPercentCommission;
                        $item->tenmg_commission_percent = $tenmgPercent;
                        $item->save();
                    }


                } else {

                    $product = EcommerceProduct::find($request->productId);
                    $orderPrice = $product->actual_price - $request->discount_price;
                    $totalWeight = $request->qty * $product->weight;
                    $tenmgPercentCommission = ($tenmgPercent/100) * $orderPrice;

                    //add item to cart
                    $cart->orderDetails()->create([
                        'ecommerce_product_id' => $request->productId,
                        'supplier_id' => $product->business_id,
                        'actual_price' => $product->actual_price * $request->qty,
                        'discount_price' => $orderPrice * $request->qty,
                        'tenmg_commission' => $tenmgPercentCommission,
                        'tenmg_commission_percent' => $tenmgPercent,
                        'quantity' => $request->qty
                        ]);
                }

                //update cart total
                $cart->order_total = $cart->orderDetails()->sum('discount_price');
                $cart->grand_total = $cart->orderDetails()->sum('discount_price')+$cart->orderDetails()->sum('tenmg_commission');
                $cart->qty_total = $cart->orderDetails()->sum('quantity');
                $cart->save();

            } else {
                if($request->action != "add"){
                    throw new \Exception("Cart does not exist");
                }
                $product = EcommerceProduct::find($request->productId);
                $orderPrice = (float)$product->actual_price - (float)$product->discount_price;
                $totalWeight = $request->qty * $product->weight;
                $tenmgPercentCommission = ($tenmgPercent/100) * $orderPrice;
                //create new cart
                $cart = EcommerceOrder::create([
                    'customer_id' => Auth::id(),
                    'qty_total' => $request->qty,
                    'order_total' => $orderPrice * $request->qty,
                    'grand_total' => $orderPrice * $request->qty+$tenmgPercentCommission,
                    'delivery_address' => "address",
                    'status' => 'CART',
                    'logistic_total' => 0,
                    'total_weight' => $totalWeight
                ]);

                //add item to cart
                $cart->orderDetails()->create([
                    'ecommerce_product_id' => $request->productId,
                    'supplier_id' => $product->business_id,
                    'actual_price' => $product->actual_price * $request->qty,
                    'discount_price' => $orderPrice * $request->qty,
                    'tenmg_commission' => $tenmgPercentCommission,
                    'tenmg_commission_percent' => $tenmgPercent,
                    'quantity' => $request->qty
                ]);
            } //

            DB::commit();

            return $cart;

        } catch (\Throwable $th) {
            //throw $th;
            DB::rollBack();
            throw $th;
        }

    }

    function syncCartItems(Request $request)
    {
        try {

            $cart = EcommerceOrder::where('id', $request->cartId)->where('status', 'CART')->first();
            //get cart items
            // $cartItems = $cart->orderDetails()->get();
            $creditSettings = new CreditSettings();
            $tenmgPercent = $creditSettings->tenmg_ecommerce_commission_percent;


            for ($i=0; $i < count($request->items); $i++) {

                $orderItem = EcommerceOrderDetail::find($request->items[$i]['itemId']);
                //get product for the items
                $product = EcommerceProduct::find($orderItem->ecommerce_product_id);
                $orderPrice = $product->actual_price - $product->discount_price;
                $quantity = $request->items[$i]['quantity'];
                $tenmgPercentCommission = ($tenmgPercent/100) * $orderPrice;

                $orderItem->quantity = $quantity;
                $orderItem->actual_price = $product->actual_price * $quantity;
                $orderItem->discount_price = $orderPrice * $quantity;
                $orderItem->tenmg_commission = $tenmgPercentCommission;
                $orderItem->tenmg_commission_percent = $tenmgPercent;
                $orderItem->save();


            }

            $cart->order_total = $cart->orderDetails()->sum('discount_price');
            $cart->grand_total = $cart->orderDetails()->sum('discount_price')+$cart->orderDetails()->sum('tenmg_commission');
            $cart->qty_total = $cart->orderDetails()->sum('quantity');
            $cart->save();

            return $cart;


        } catch (\Throwable $th) {
            throw $th;
        }
    }

    function getUserCart()
    {
        try {
            $cart = EcommerceOrder::where('customer_id', Auth::id())->where('status', 'CART')->first();
            return $cart;
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    function buyNow(Request $request)
    {
        try {

                $creditSettings = new CreditSettings();
                $tenmgPercent = $creditSettings->tenmg_ecommerce_commission_percent;

                $product = EcommerceProduct::find($request->productId);
                $orderPrice = (float)$product->actual_price - (float)$product->discount_price;
                $totalWeight = $request->qty * $product->weight;
                $tenmgPercentCommission = ($tenmgPercent/100) * $orderPrice;

            $createOrder = EcommerceOrder::create([
                'customer_id' => Auth::id(),
                'qty_total' => $request->qty,
                'order_total' => $orderPrice * $request->qty,
                'grand_total' => $orderPrice * $request->qty+$tenmgPercentCommission,
                'delivery_address' => "address",
                'status' => 'PENDING',
                'logistic_total' => 0,
                'total_weight' => $totalWeight
            ]);

            $createOrder->orderDetails()->create([
                'ecommerce_product_id' => $request->productId,
                'supplier_id' => $product->business_id,
                'actual_price' => $product->actual_price * $request->qty,
                'discount_price' => $orderPrice * $request->qty,
                'tenmg_commission' => $tenmgPercentCommission,
                'tenmg_commission_percent' => $tenmgPercent,
                'quantity' => $request->qty
            ]);

            return $createOrder;

        } catch (\Throwable $th) {
            throw $th;
        }
    }

    function clearUserCart()
    {
        try {
            $cart = EcommerceOrder::where('customer_id', Auth::id())->where('status', 'CART')->first();
            if (!$cart) {
                throw new \Exception("Cart does not exist");
            }
            $cart->orderDetails()->delete();
            $cart->delete();

            return $cart;
        } catch (\Throwable $th) {
            throw $th;
        }

    }


}
