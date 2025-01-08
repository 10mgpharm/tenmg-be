<?php

namespace App\Http\Controllers\API\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Resources\EcommerceCartResource;
use App\Services\Admin\Storefront\EcommerceCartService;
use Illuminate\Http\Request;

class CartController extends Controller
{

    protected $ecommerceCartService;

    function __construct(EcommerceCartService $ecommerceCartService)
    {
        $this->ecommerceCartService = $ecommerceCartService;
    }

    function addRemoveItemToCart(Request $request)
    {
        $request->validate([
            'productId' => 'required|exists:ecommerce_products,id',
            'qty' => 'required|integer|min:1',
            'action' => 'required|in:add,remove,minus',
        ]);

        $cart = $this->ecommerceCartService->addRemoveItemToCart($request);
        return $this->returnJsonResponse(message: 'Success', data: $cart);
    }

    function getUserCart()
    {
        $cart = $this->ecommerceCartService->getUserCart();
        return $this->returnJsonResponse(message: 'User cart', data: EcommerceCartResource::collection($cart));
    }


}
