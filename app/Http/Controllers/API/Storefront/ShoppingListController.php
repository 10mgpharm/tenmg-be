<?php

namespace App\Http\Controllers\API\Storefront;
use App\Http\Controllers\Controller;
use App\Http\Resources\EcommerceShoppingListResource;
use App\Services\Storefront\ShoppingListService;
use Illuminate\Http\Request;

class ShoppingListController extends Controller
{
    protected $shoppingListService;

    function __construct(ShoppingListService $shoppingListService)
    {
        $this->shoppingListService = $shoppingListService;
    }

    function addShoppingList(Request $request)
    {
        $request->validate([
            'productName' => 'required',
            'brandName' => 'required',
            'purchaseDate' => 'nullable|date|after_or_equal:today',
            'description' => 'required'
        ]);
        $shoppingList = $this->shoppingListService->addShoppingList($request);
        return $this->returnJsonResponse(
            message: 'Item added to shopping list successfully',
            data: $shoppingList
        );
    }

    function getShoppingList()
    {
        $list = $this->shoppingListService->getShoppingList();
        return $this->returnJsonResponse(
            message: 'Shopping list retrieved successfully',
            data: EcommerceShoppingListResource::collection($list)
        );
    }

    function removeItemFromSHoppingList($id)
    {
        $deleted = $this->shoppingListService->removeItemFromShoppingList($id);
        return $this->returnJsonResponse(
            message: 'Item removed from shopping list successfully',
            data: $deleted
        );
    }

}
