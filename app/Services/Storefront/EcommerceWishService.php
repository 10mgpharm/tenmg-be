<?php

namespace App\Services\Storefront;

use App\Repositories\WishListRepository;
use Illuminate\Http\Request;

class EcommerceWishService
{
    protected $wishListRepository;

    function __construct(WishListRepository $wishListRepository)
    {
        $this->wishListRepository = $wishListRepository;
    }

    function addWishList(Request $request)
    {
        try {

            $wishList = $this->wishListRepository->addWishList($request);
            return $wishList;

        } catch (\Throwable $th) {
            throw $th;
        }
    }

    function getWhishList()
    {
        try {

            $wishList = $this->wishListRepository->getWhishList();

            return $wishList;

        } catch (\Throwable $th) {
            throw $th;
        }
    }

    function removeProductFromWishList($id)
    {
        try {

            $this->wishListRepository->removeProductFromWishList($id);

        } catch (\Throwable $th) {
            throw $th;
        }
    }

}
