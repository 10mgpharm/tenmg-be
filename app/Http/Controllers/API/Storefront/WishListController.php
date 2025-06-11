<?php

namespace App\Http\Controllers\API\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Resources\EcommerceWishListResource;
use App\Services\Storefront\EcommerceWishService;
use Illuminate\Http\Request;

class WishListController extends Controller
{

    protected $ecommerceWishService;

    function __construct(EcommerceWishService $ecommerceWishService)
    {
        $this->ecommerceWishService = $ecommerceWishService;
    }

    function addWishList(Request $request)
    {

        $request->validate([
            'productId' => 'required|exists:ecommerce_products,id'
        ]);

        $wishList = $this->ecommerceWishService->addWishList($request);

        return $this->returnJsonResponse(
            message: 'Product added to wishlist successfully',
            data: $wishList
        );
    }

    function getWhishList()
    {
        $wishList = $this->ecommerceWishService->getWhishList();
        return $this->returnJsonResponse(
            message: 'Wish list fetched successfully',
            data: EcommerceWishListResource::collection($wishList)
        );
    }

    function removeProductFromWishList($id)
    {
        $this->ecommerceWishService->removeProductFromWishList($id);
        return $this->returnJsonResponse(
            message: 'Product removed from wishlist successfully'
        );
    }

}
