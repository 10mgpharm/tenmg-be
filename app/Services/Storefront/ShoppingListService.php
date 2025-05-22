<?php

namespace App\Services\Storefront;

use App\Repositories\ShoppingListRepository;
use Exception;

class ShoppingListService
{

    protected $shoppingListRepository;

    function __construct(ShoppingListRepository $shoppingListRepository)
    {
        $this->shoppingListRepository = $shoppingListRepository;
    }

    function addShoppingList($request)
    {
        try {
            return $this->shoppingListRepository->addShoppingList($request);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    function getShoppingList()
    {
        try {
            return $this->shoppingListRepository->getShoppingList();
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    function getShoppingListAdmin(array $filters, $perPage)
    {

        try {
            return $this->shoppingListRepository->getShoppingListAdmin($filters, $perPage);
        } catch (\Throwable $th) {
            throw $th;
        }

    }

    function removeItemFromSHoppingList($id)
    {
        try {
            return $this->shoppingListRepository->removeItemFromSHoppingList($id);
        } catch (\Throwable $th) {
            throw $th;
        }
    }



}
