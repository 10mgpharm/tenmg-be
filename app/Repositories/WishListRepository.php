<?php

namespace App\Repositories;

use App\Models\EcommerceWishList;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WishListRepository
{

    function addWishList(Request $request)
    {

        //check if the product userid pair exist for this user
        $wishList = EcommerceWishList::where('user_id', Auth::id())->where('product_id', $request->productId)->first();
        if ($wishList) {
            throw new \Exception('Product already added to wishlist');
        }

        $wishList = new EcommerceWishList();
        $wishList->user_id = Auth::id();
        $wishList->product_id = $request->productId;
        $wishList->save();

        return $wishList;
    }

    function getWhishList()
    {
        $wishList = EcommerceWishList::where('user_id', Auth::id())->get();
        return $wishList;
    }

    function removeProductFromWishList($id)
    {
        $removeProduct = EcommerceWishList::find($id);
        if (!$removeProduct) {
            throw new \Exception('Wishlist does not exist');
        }
        $removeProduct->delete();
    }

}
