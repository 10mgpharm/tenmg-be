<?php

namespace App\Repositories;

use App\Enums\InAppNotificationType;
use App\Enums\MailType;
use App\Helpers\UtilityHelper;
use App\Mail\Mailer;
use App\Models\EcommerceOrder;
use App\Models\EcommercePayment;
use App\Models\EcommercePaymentMethod;
use App\Models\User;
use App\Services\InAppNotificationService;
use Illuminate\Bus\Batch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Mail;
use Throwable;

class EcommercePaymentRepository
{

    public function getPaymentMethods()
    {
        $paymentMethods = EcommercePaymentMethod::where('status', 'ACTIVE')->get();

        return $paymentMethods;
    }

    public function initializePayment(Request $request)
    {
        //get the order by id
        $order = EcommerceOrder::find($request->orderId);

        //ensure order is in cart
        if ($order->status != 'CART') {
            throw new \Exception('Order is not in cart');
        }

        $amount = $order->grand_total;
        $transactionFee = $order->shipping_fee;
        $reference = UtilityHelper::generateSlug('PAY');

        $order->delivery_type = $request->deliveryType;
        $order->delivery_address = $request->deliveryAddress;
        $order->payment_status = "PAYMENT_INITIATED";
        $order->save();

        //ensure we have only one instance of order payment for each order
        $orderPayment = EcommercePayment::where('order_id', $order->id)->first();

        //create or update ecommerce payment
        $orderPayment = $orderPayment ?? new EcommercePayment;
        $orderPayment->order_id = $order->id;
        $orderPayment->status = 'initiated';
        $orderPayment->reference = $reference;
        $orderPayment->customer_id = Auth::id();
        $orderPayment->amount = $amount;
        $orderPayment->fee = $transactionFee;
        $orderPayment->total_amount = $amount + $transactionFee;
        $orderPayment->channel = $request->paymentMethod;
        $orderPayment->currency = 'NGN';
        $orderPayment->save();

        $user = $request->user();
        $business = $user->ownerBusinessType ?? $user->businesses()->first();
        $productNames = $order->orderDetails->pluck('product.name')->implode(', ');

        // Prepare admin recipients
        $admins = User::role('admin')->get();

        // Create a batch of jobs for notifications and emails
        Bus::batch([
            // In-app notifications
            fn () => (new InAppNotificationService)
                ->notify(InAppNotificationType::NEW_ORDER_PAYMENT_STOREFRONT),

            // fn () => (new InAppNotificationService)
            //     ->forUser($user)
            //     ->notify(InAppNotificationType::NEW_ORDER_PAYMENT_SUPPLIER),

            fn () => (new InAppNotificationService)
                ->forUsers($admins)
                ->notify(InAppNotificationType::NEW_ORDER_PAYMENT_ADMIN),

            // Queued emails
            fn () => Mail::to($user->email)->queue(new Mailer(MailType::NEW_ORDER_PAYMENT_STOREFRONT, [
                'name' => $user->name,
                'order' => $order,
            ])),

            // fn () => Mail::to($user->email)->queue(new Mailer(MailType::NEW_ORDER_PAYMENT_SUPPLIER, [
            //     'name' => $user->name,
            //     'productNames' => $productNames,
            //     'order' => $order,
            //     'netAmount' => null,
            // ])),

            fn () => Mail::to($user->email)->queue(new Mailer(MailType::NEW_ORDER_PAYMENT_ADMIN, [
                'order' => $order,
                'productNames' => $productNames,
                'pharmacyName' => $business?->name,
            ])),
        ])
        ->name('New Order Payment Notifications & Mails')
        ->allowFailures()
        ->catch(function (Batch $batch, Throwable $e) {
            logs()->error('Batch failed: ' . $e->getMessage());
        })
        ->dispatch();

        return $orderPayment;
    }

}
