<?php

namespace App\Repositories;

use App\Models\EcommerceShopingList;
use App\Services\AttachmentService;
use Illuminate\Support\Facades\Auth;

class ShoppingListRepository
{

    private AttachmentService $attachmentService;

    function __construct(AttachmentService $attachmentService)
    {
        $this->attachmentService = $attachmentService;
    }

    function addShoppingList($request)
    {

        //create new shopping list
        $shoppingList = new EcommerceShopingList();
        $shoppingList->product_name = $request->productName;
        $shoppingList->brand_name = $request->brandName;
        $shoppingList->purchase_date = $request->purchaseDate;
        $shoppingList->description = $request->description;
        $shoppingList->user_id = Auth::id();
        $shoppingList->product_id = $request->productId;
        $shoppingList->save();

        // Save uploaded file
        if ($request->hasFile('file')) {
            $created = $this->attachmentService->saveNewUpload(
                $request->file('file'),
                $shoppingList->id,
                EcommerceShopingList::class,
            );

            //updateShoppingList
            $shoppingList = EcommerceShopingList::find($shoppingList->id);
            $shoppingList->shopping_list_image_id = $created->id;
            $shoppingList->save();
        }

        return $shoppingList;

    }

    function getShoppingList()
    {
        $list = EcommerceShopingList::where('user_id', Auth::id())->orderBy('created_at', 'DESC')->get();
        return $list;
    }

    function getShoppingListAdmin($filters, $perPage):\Illuminate\Contracts\Pagination\LengthAwarePaginator
    {

        $query = EcommerceShopingList::query();

        // Search logic
        $query->when(isset($filters['search']), function ($query) use ($filters) {
            $searchTerm = "%{$filters['search']}%";
            return $query->where(function ($query) use ($searchTerm) {
                $query->where('product_name', 'like', $searchTerm);
            });
        });

        $list = $query->orderBy('created_at', 'DESC')->paginate($perPage);

        return $list;
    }

    function removeItemFromSHoppingList($id)
    {
        $item = EcommerceShopingList::find($id);
        //check if exist
        if(!$item){
            throw new \Exception('Item not found');
        }
        //check if user is owner
        if($item->user_id != Auth::id()){
            throw new \Exception('You are not allowed to delete this item');
        }
        $item->delete();
        return $item; //
    }

}
