<?php

use App\Models\EcommerceOrder;
use App\Models\EcommercePayment;
use App\Models\EcommerceShopingList;
use App\Models\User;
use App\Repositories\FincraPaymentRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class TenmgPaymentRepository
{

    function __construct(private FincraPaymentRepository $fincraPaymentRepository)
    {

    }


    public function verifyTenmgCreditPaymentWebHook(Request $request)
    {

        $merchantWebhookSecretKey = config('services.tenmg.secret');
        $payload = $request->getContent();

        // Generate the HMAC using SHA512
        $encryptedData = hash_hmac('sha512', $payload, $merchantWebhookSecretKey);

        // Get the signature from the request headers
        $signatureFromWebhook = $request->header('X-Signature');

        // Compare the generated HMAC with the signature from the webhook
        if ($encryptedData === $signatureFromWebhook) {

            $data = json_decode($payload);

            $event = $data->event;

            switch ($event) {
                case 'application.submitted':
                    // $this->resolveTransaction($data);
                    break;
                case 'application.approved':
                    $this->completeOrder($data);
                    break;
                // TODO: application.rejected
                default:

                    break;
            }

        } else {


        }

    }


    public function completeOrder($data)
    {
        $body = $data->data;
        $merchantReference = $body->merchantReference;
        $status = $body->status;

        if ($status != 'successful') {
            throw new \Exception('Payment not successful');
        }

        // //get the order payment instance
        $orderPayment = EcommercePayment::where('reference', $merchantReference)->first();
        // //update external reference
        $orderPayment->external_reference = $body->reference;
        $orderPayment->status = 'success';
        $orderPayment->paid_at = now();
        $orderPayment->meta = json_encode($body);
        $orderPayment->save();

        //update order status
        $order = EcommerceOrder::find($orderPayment->order_id);
        $order->status = 'PENDING';
        $order->payment_status = "PAYMENT_SUCCESSFUL";
        $order->save();

        //send email to customer.
        $customer = User::find($order->customer_id);
        $customer->sendOrderConfirmationNotification('Your payment with id '.$merchantReference.' was successful. Your order is processing.', $customer);

        //send email to supplier
        $orderItems = $order->orderDetails;

        $this->removeBoughtItemFromShoppingList($orderItems);

        for ($i = 0; $i < count($orderItems); $i++) {
            $product = $orderItems[$i]->product;
            $supplier = $orderItems[$i]->supplier;
            $owner = $supplier->owner;

            //Reduce product quantity
            $product->quantity = $product->quantity - $orderItems[$i]->quantity;
            $product->save();

            $owner->sendOrderConfirmationNotification('You have a new order from '.$order->customer->name.'. Order for '.$product->name, $owner);
        }

        $admins = User::role('admin')->get();
        foreach ($admins as $admin) {
            $admin->sendOrderConfirmationNotification('New order from '.$order->customer->name, $admin);
        }

        return $orderPayment;

    }

    public function removeBoughtItemFromShoppingList($orderItems)
    {
        for ($i = 0; $i < count($orderItems); $i++) {
            EcommerceShopingList::where('user_id', Auth::id())->where('product_id', $orderItems[$i]->ecommerce_product_id)->delete();
        }
    }

}
