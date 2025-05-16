<?php

namespace App\Services\Admin\Storefront;

use App\Models\EcommerceCart;
use App\Models\EcommerceOrder;
use App\Models\EcommerceOrderDetail;
use App\Models\EcommerceProduct;
use App\Models\EcommerceWallet;
use App\Repositories\OrderRepository;
use App\Services\AuditLogService;
use App\Services\SupplierOrderWalletService;
use App\Settings\CreditSettings;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EcommerceCartService
{
    protected $orderRepository;

    public function __construct(OrderRepository $orderRepository)
    {
        $this->orderRepository = $orderRepository;
    }

    function getOrderByStatus(Request $request)
    {
        return $this->orderRepository->getOrderByStatus($request);
    }

    function getOrderByStatusCount()
    {
        return $this->orderRepository->getOrderByStatusCount();
    }

    function changeOrderStatus(Request $request)
    {
        try {

            $order = EcommerceOrder::find($request->input('orderId'));
            $user = $request->user();

            $allowed_transitions = [
                'PENDING' => ['PROCESSING', 'CANCELED'],
                'PROCESSING' => ['SHIPPED'],
                'SHIPPED' => ['COMPLETED', 'DELIVERED'],
                'CANCELED' => ['REFUNDED'],
                'AWAITING_REFUND' => ['REFUNDED'],
            ];

            if (!isset($allowed_transitions[$order->status])) {
                return response()->json([
                    'message' => "Orders with '{$order->status}' status cannot be updated.",
                    'status' => 'error',
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            if (!in_array($request->input('status'), $allowed_transitions[$order->status]) && $request->input('status') !== $order->status) {
                return response()->json([
                    'message' => "Order cannot be moved from '{$order->status}' to '{$request->input('status')}'.",
                    'status' => 'error',
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
    
            switch ($order->status) {
                case 'PENDING':
                    if($request->input('status') == 'CANCELED'){
                        AuditLogService::log(
                            target: $order,
                            event: 'order.canceled',
                            action: "Order cancelled",
                            description: "An order has been canceled now awaiting a refund.",
                            crud_type: 'UPDATED', // Use 'UPDATE' for updating actions
                            properties: [
                                'order_id' => $order->id,
                                'order_number' => $order->order_number,
                                'status' => $request->input('status'),
                                'reason' => $request->input('reason'),
                                'requires_refund' => $request->input('requiresRefund'),
                            ]

                        );
                    }
                    break;

                case 'CANCELED':
                    if($request->input('refundStatus') == 'REFUNDED'){
                        $business = $order->customer->ownerBusinessType
                            ?: $order->customer->businesses()->firstWhere('user_id', $user->id);
                        AuditLogService::log(
                            target: $order, 
                            event: 'order.refunded',
                            action: "Cancelled order refunded",
                            description: "{$business?->name} has now been refunded for the canceled order.",
                            crud_type: 'UPDATED', // Use 'UPDATE' for updating actions
                            properties: [
                                'order_id' => $order->id,
                                'order_number' => $order->order_number,
                                'status' => $request->input('status'),
                                'reason' => $request->input('reason'),
                                'refund_status' => $request->input('refundStatus'),
                                'requires_refund' => $request->input('requiresRefund'),
                            ]

                        );
                    } else if($request->input('refundStatus') == 'AWAITING REFUND'){
                        $business = $order->customer->ownerBusinessType
                            ?: $order->customer->businesses()->firstWhere('user_id', $user->id);
                        AuditLogService::log(
                            target: $order, 
                            event: 'order.un-refunded',
                            action: "Cancelled order un-refunded",
                            description: "{$business?->name} has now been canceled, no refund has been made.",
                            crud_type: 'UPDATED', // Use 'UPDATE' for updating actions
                            properties: [
                                'order_id' => $order->id,
                                'order_number' => $order->order_number,
                                'status' => $request->input('status'),
                                'reason' => $request->input('reason'),
                                'refund_status' => $request->input('refundStatus'),
                                'requires_refund' => $request->input('requiresRefund'),
                            ]
                        );
                    }
                    break;
                
                default:
                    # code...
                    break;
            }
            $order->status = $request->input('status');
            $order->refund_status = $request->input('refundStatus');
            $order->requires_refund = $request->input('requiresRefund');
            $order->save();


            
            switch ($order->status) {
                case 'COMPLETED':
                    // If the order is completed, credit the supplier(s)
                    (new SupplierOrderWalletService)->credit($order);
                    break;

                case 'CANCELED':
                    // If the order is canceled, we need to debit the supplier(s)
                    (new SupplierOrderWalletService)->debit($order);
                    break;
            }

            
            return $order;

        } catch (\Throwable $th) {
            throw $th;
        }

    }

    function getOrderDetails($id)
    {
        return $this->orderRepository->getOrderDetails($id);
    }


}
