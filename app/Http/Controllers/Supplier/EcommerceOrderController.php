<?php

namespace App\Http\Controllers\Supplier;

use App\Enums\StatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Resources\EcommerceCartResource;
use App\Http\Resources\EcommercePresentationResource;
use App\Models\EcommercePresentation;
use App\Services\Storefront\EcommerceOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EcommerceOrderController extends Controller
{
    protected $ecommerceOrderService;

    function __construct(EcommerceOrderService $ecommerceOrderService)
    {
        $this->ecommerceOrderService = $ecommerceOrderService;
    }

    function getOrderByStatusSuppliers(Request $request)
    {
        $orders = $this->ecommerceOrderService->getOrderByStatusSuppliers($request);
        return $this->returnJsonResponse(
            data: EcommerceCartResource::collection($orders)->response()->getData(true)
        );
    }

    function getOrderDetailsSuppliers($id)
    {
        $orderDetails = $this->ecommerceOrderService->getOrderDetailsSuppliers($id);

        return $this->returnJsonResponse(
            data: new EcommerceCartResource($orderDetails)
        );
    }



}
