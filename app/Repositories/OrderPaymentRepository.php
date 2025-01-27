<?php

namespace App\Repositories;

use App\Models\EcommerceOrder;
use Illuminate\Http\Request;

class OrderPaymentRepository
{

    function initializePayment(Request $request)
    {
        //get the order by id
        $order = EcommerceOrder::find($request->orderId);
        $amount = $order->grand_total;

        return $this->calculateTransactionFee($amount, 'NGN');
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

}
