<?php

namespace App\Http\Controllers\API\Storefront;

use App\Http\Controllers\Controller;
use App\Services\Admin\Storefront\EcommerceCartService;
use Illuminate\Http\Request;

class CartController extends Controller
{

    protected $ecommerceCartService;

    function __construct(EcommerceCartService $ecommerceCartService)
    {
        $this->ecommerceCartService = $ecommerceCartService;
    }

    function addItemToCart(Request $request)
    {
        $request->validate([
            'productId' => 'required|exists:ecommerce_products,id',
            'qty' => 'required|integer|min:1',
            'action' => 'required|in:add,remove',
        ]);

        $cart = $this->ecommerceCartService->addItemToCart($request);

        return $this->returnJsonResponse(message: 'Product added to cart', data: $cart);


    }


}
