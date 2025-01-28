<?php

namespace App\Repositories;

use App\Helpers\UtilityHelper;
use App\Models\EcommerceOrder;
use App\Models\EcommercePayment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FincraPaymentRepository
{

    function initializePayment(Request $request)
    {
        //get the order by id
        $order = EcommerceOrder::find($request->orderId);

        //ensure order is in cart
        if ($order->status != "CART") {
            throw new \Exception("Order is not in cart");
        }

        $amount = $order->grand_total;
        $transactionFee = 100;
        $reference = UtilityHelper::generateSlug('PAY');

        $order->delivery_type = $request->deliveryType;
        $order->delivery_address = $request->deliveryAddress;
        $order->save();

        //ensure we have only one instance of order payment for each order
        $orderPayment = EcommercePayment::where('order_id', $order->id)->first();

        //create or update ecommerce payment
        $orderPayment = $orderPayment ?? new EcommercePayment();
        $orderPayment->order_id = $order->id;
        $orderPayment->status = "initiated";
        $orderPayment->reference = $reference;
        $orderPayment->customer_id = Auth::id();
        $orderPayment->amount = $amount;
        $orderPayment->fee = $transactionFee;
        $orderPayment->total_amount = $amount + $transactionFee;
        $orderPayment->channel = "fincra";
        $orderPayment->currency = "NGN";
        $orderPayment->save();

        return $orderPayment;
    }

    function calculateTransactionFee($amount, $currency)
    {

        $apiUrl = "https://api.fincra.com/checkout/data/fees"; // Example endpoint

        // You’d typically retrieve your secret key from a secure location (env variable, config, etc.)
        $secretKey = env('YOUR_FINCRA_SECRET_KEY');

        // Prepare the data you need to send. This will vary based on Fincra's documentation.
        $postData = array(
            'amount'   => $amount,
            'currency' => $currency,
            'type' => 'card'
        );

        $headers = array(
            "Content-Type: application/json",
            "Authorization: Bearer $secretKey",
        );

        // Initialize cURL
        $ch = curl_init($apiUrl);

        // Set cURL options
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // Execute request
        $response = curl_exec($ch);
        $err      = curl_error($ch);

        // Close connection
        curl_close($ch);

        // Handle errors or parse the response
        if ($err) {
            // Handle the error (log it, throw exception, etc.)
            return $err;
            return null;
        } else {
            // Decode JSON response
            return $response;
            $data = json_decode($response, true);

            // The key for the calculated fee in the response depends on Fincra's actual API structure
            // For example, let’s say it’s returned as `$data['data']['fee']`
            if (isset($data['data']['fee'])) {
                return $data['data']['fee'];
            }

            return null;
        }
    }

    function verifyFincraPayment($ref)
    {

        $curl = curl_init();
        curl_setopt_array($curl, [
        CURLOPT_URL => env('FINCRA_BASE_URL')."/collections/merchant-reference/".$ref,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_HTTPHEADER => [
            "accept: application/json",
            "api-key: ".env('FINCRA_SECRET_KEY')
        ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            echo "cURL Error #:" . $err;
        } else {
           return $this->completeOrder(json_decode($response));
        }

    }

    function completeOrder($data)
    {
        $body = $data->data;
        $merchantReference = $body->merchantReference;
        $status = $body->status;

        if($status != "successful"){
            throw new \Exception("Payment not successful");
        }

        // //get the order payment instance
        $orderPayment = EcommercePayment::where('reference', $merchantReference)->first();
        // //update external reference
        $orderPayment->external_reference = $body->reference;
        $orderPayment->status = "success";
        $orderPayment->paid_at = now();
        $orderPayment->meta = json_encode($body);
        $orderPayment->save();

        //update order status
        $order = EcommerceOrder::find($orderPayment->order_id);
        $order->status = "PENDING";
        $order->save();

        //send email to customer.
        $customer = User::find($order->customer_id);
        $customer->sendOrderConfirmationNotification('Your payment with id '.$merchantReference.' was successful. Your order is processing.', $customer);

        //send email to supplier
        $orderItems = $order->orderDetails;

        for ($i=0; $i < count($orderItems); $i++) {
            $product = $orderItems[$i]->product;
            $supplier = $orderItems[$i]->supplier;
            $owner = $supplier->owner;
            $owner->sendOrderConfirmationNotification('You have a new order from '.$order->customer->name.'. Order for '.$product->name, $owner);
        }

        $admins = User::role('admin')->get();
        foreach ($admins as $admin) {
            $admin->sendOrderConfirmationNotification('New order from '.$order->customer->name, $admin);
        }

        return $orderPayment;

    }

    function cancelPayment($ref)
    {
        $orderPayment = EcommercePayment::where('reference', $ref)->first();
        if(!$orderPayment){
            throw new \Exception("Order payment not found");
        }
         if($orderPayment->status != "initiated"){
            throw new \Exception("Order payment not initiated");
        }
        $orderPayment->status = "abandoned";
        $orderPayment->save();
    }
}
