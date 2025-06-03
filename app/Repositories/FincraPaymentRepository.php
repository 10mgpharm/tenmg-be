<?php

namespace App\Repositories;

use App\Enums\InAppNotificationType;
use App\Enums\MailType;
use App\Helpers\UtilityHelper;
use App\Http\Resources\BusinessResource;
use App\Jobs\SendInAppNotification;
use App\Mail\Mailer;
use App\Models\Business;
use App\Models\CreditOffer;
use App\Models\CreditRepaymentPayments;
use App\Models\CreditTransactionHistory;
use App\Models\DebitMandate;
use App\Models\EcommerceOrder;
use App\Models\EcommercePayment;
use App\Models\EcommerceShopingList;
use App\Models\Loan;
use App\Models\RepaymentSchedule;
use App\Models\User;
use App\Services\InAppNotificationService;
use Carbon\Carbon;
use Illuminate\Bus\Batch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\Http;

class FincraPaymentRepository
{

    function __construct(private FincraMandateRepository $fincraMandateRepository, private LenderDashboardRepository $lenderDashboardRepository, private RepaymentScheduleRepository $repaymentScheduleRepository)
    {

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
        $transactionFee = 100;
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
        $orderPayment->channel = 'fincra';
        $orderPayment->currency = 'NGN';
        $orderPayment->save();

        $user = $request->user();
        $business = $user->ownerBusinessType ?? $user->businesses()->first();
        $productNames = $order->orderDetails->pluck('product.name')->implode(', ');

        // Get all unique users linked to suppliers involved in the order
        $supplierUsers = new EloquentCollection(
            $order->orderDetails->pluck('supplier.owner')->flatten()->unique('id')->values()
        );

        // Prepare admin recipients
        $admins = User::role('admin')->get();

        // Create a batch of jobs for notifications and emails

        SendInAppNotification::dispatch(InAppNotificationType::NEW_ORDER_PAYMENT_STOREFRONT);
        SendInAppNotification::dispatch(InAppNotificationType::NEW_ORDER_PAYMENT_SUPPLIER, $supplierUsers);
        SendInAppNotification::dispatch(InAppNotificationType::NEW_ORDER_PAYMENT_ADMIN, $admins);

        /* Queued emails */
        Mail::to($user->email)->queue(new Mailer(MailType::NEW_ORDER_PAYMENT_STOREFRONT, [
            'name' => $user->name,
            'order' => $order,
        ]));
        // Suppliers email
        foreach ($supplierUsers as $supplier) {
            Mail::to($supplier->email)->queue(new Mailer(MailType::NEW_ORDER_PAYMENT_SUPPLIER, [
                'name' => $supplier->name,
                'productNames' => $productNames,
                'order' => $order,
                'netAmount' => null,
            ]));
        }
        // Admin email
        foreach ($admins as $admin) {
            Mail::to($admin->email)->queue(new Mailer(MailType::NEW_ORDER_PAYMENT_ADMIN, [
                'order' => $order,
                'productNames' => $productNames,
                'pharmacyName' => $business?->name,
            ]));
        }

        return $orderPayment;
    }

    public function calculateTransactionFee($amount, $currency)
    {

        $apiUrl = 'https://api.fincra.com/checkout/data/fees'; // Example endpoint

        // You’d typically retrieve your secret key from a secure location (env variable, config, etc.)
        $secretKey = env('YOUR_FINCRA_SECKEY');

        // Prepare the data you need to send. This will vary based on Fincra's documentation.
        $postData = [
            'amount' => $amount,
            'currency' => $currency,
            'type' => 'card',
        ];

        $headers = [
            'Content-Type: application/json',
            "Authorization: Bearer $secretKey",
        ];

        // Initialize cURL
        $ch = curl_init($apiUrl);

        // Set cURL options
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // Execute request
        $response = curl_exec($ch);
        $err = curl_error($ch);

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

    public function verifyFincraPayment($ref)
    {

        $customer = null;
        $amount = 0;

        switch (true) {
            case Str::startsWith($ref, "PAY"):
                 $res = $this->ecommerceVerify($ref);
                 $customer = $res['customer'];
                 $amount = $res['amount'];
                 $channel = $res['channel'];
                 if($channel == "tenmg_credit"){
                    return $this->completeOrderTenmgPayment($ref);
                 }
                break;
            case Str::startsWith($ref, "THG"):
                // This is a transaction history reference
                $res = $this->transactionHistoryVerify($ref);
                $customer = $res['customer'];
                $amount = $res['amount'];
                break;
            case Str::startsWith($ref, "LNR"):
                // This is a loan repayment reference
                $res = $this->loanRepaymentVerify($ref);
                $customer = $res['customer'];
                $amount = $res['amount'];
                break;
            default:
                throw new \Exception('Invalid reference');
        }

        //check if it is development environment
        if (config('app.env') != 'production') {
            $data = $this->mockFincraSuccessResponse($customer, $amount, $ref);

            return $this->resolveTransaction($data);
        }

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => config('services.fincra.url').'/collections/merchant-reference/'.$ref,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => [
                'accept: application/json',
                'api-key: '.config('services.fincra.secret'),
            ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);


        $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);

        if ($err) {
            throw new \Exception($err);
        } else {
            if ($statusCode == 200) {
                return $this->resolveTransaction(json_decode($response));
            }
            return $this->changePaymentToPending($ref);
            $data = json_decode($response, true);

            if($data['message'] == "no Route matched with those values"){
                throw new \Exception("No response from Fincra");
            }
        }

    }

    public function ecommerceVerify($ref)
    {

        //check if we have a payment with this ref
            $payment = EcommercePayment::where('reference', $ref)->first();
            if (! $payment) {
                throw new \Exception('Payment not found');
            }

            if ($payment->status != 'initiated') {
                throw new \Exception('Payment already processed');
            }

            $customerData = User::find($payment->customer_id);
            $customer = [
                'name' => $customerData->name,
                'email' => $customerData->email,
                'phone' => $customerData->phone,
                "bank_code"=> null,
                "card_scheme"=> "mastercard"
            ];

            $amount = $payment->total_amount;

            return [
                'customer' => $customer,
                'amount' => $amount,
                'channel' => $payment->channel
            ];

    }

    public function transactionHistoryVerify($ref)
    {

        //check if we have a payment with this ref
        $payment = CreditTransactionHistory::where('identifier', $ref)->where('transaction_group', 'deposit')->first();
        if (! $payment) {
            throw new \Exception('No payment exist with this reference');
        }

        if ($payment->status == 'cancelled') {
            throw new \Exception('Payment has been cancelled');
        }

        if ($payment->status == 'success') {
            throw new \Exception('Payment already processed');
        }

        $business = Business::find($payment->business_id)->owner;

        $customer = [
            'name' => $business->name,
            'email' => $business->email,
            'phone' => $business->phone,
            "bank_code"=> null,
            "card_scheme"=> "mastercard"
        ];

        $amount = $payment->amount;

        return [
            'customer' => $customer,
            'amount' => $amount
        ];

    }

    public function loanRepaymentVerify($ref)
    {

        //check if we have a payment with this ref
        $payment = CreditRepaymentPayments::where('reference', $ref)->first();
        if (! $payment) {
            throw new \Exception('No payment exist with this reference');
        }

        if ($payment->status == 'cancelled') {
            throw new \Exception('Payment has been cancelled');
        }

        if ($payment->status == 'success') {
            throw new \Exception('Payment already processed');
        }

        $business = Business::find($payment->business_id)->owner;

        $customer = [
            'name' => $business->name,
            'email' => $business->email,
            'phone' => $business->phone,
            "bank_code"=> null,
            "card_scheme"=> "mastercard"
        ];

        $amount = $payment->amount;

        return [
            'customer' => $customer,
            'amount' => $amount
        ];

    }

    public function changePaymentToPending($ref)
    {

        if(Str::startsWith($ref, "PAY")){

            // //get the order payment instance
            $orderPayment = EcommercePayment::where('reference', $ref)->first();
            //check if payment record exist
            if(!$orderPayment){
                throw new \Exception('Payment not found');
            }
            // //update external reference
            $orderPayment->status = 'pending';
            $orderPayment->save();

            //get order for payment
            $order = EcommerceOrder::find($orderPayment->order_id);
            $order->payment_status = "PENDING_PAYMENT_CONFIRMATION";
            $order->save();

            return $orderPayment;

        }elseif(Str::startsWith($ref, "THG")){

            $transaction = CreditTransactionHistory::where('identifier', $ref)->first();
            if(!$transaction){
                throw new \Exception('Transaction not found');
            }
            $transaction->status = 'pending';
            $transaction->save();

            return $transaction;

        }else{
            throw new \Exception('Invalid reference');
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

    public function completeOrderTenmgPayment($ref)
    {
        $body = null;
        $status = "initiated";
        $externalReference = null;


        //make a http call to 10mg payment api to verify the payment using
        $data = [
            'reference' => $ref,
        ];

        // Send the POST request
        $response = Http::withHeaders([
            'Secret-Key' => config('services.tenmg.secret'),
            'Accept' => 'application/json',
        ])->post(config('services.tenmg.url')."/api/v1/client/applications/status", $data);


        if($response->successful()){

            $res = $response->json();

            // return $res['data'];

            $status = $res['data']['orderStatus'] == "PAID" ? 'success':'initiated';
            $externalReference = $res['data']['application']['identifier'];
            $body = $res['data'];

        }else{

        }

        // //get the order payment instance
        $orderPayment = EcommercePayment::where('reference', $ref)->first();

        // return $status;

        if ($status != 'success') {
            // throw new \Exception('Your payment is processing');
            $orderPayment['applicationStatus'] = $body['orderStatus'];
            return $orderPayment;
        }


        // //update external reference
        $orderPayment->external_reference = $externalReference;
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
        $customer->sendOrderConfirmationNotification('Your payment with id '.$ref.' was successful. Your order is processing.', $customer);

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

    public function cancelPayment($ref)
    {
        $orderPayment = EcommercePayment::where('reference', $ref)->first();
        if (! $orderPayment) {
            throw new \Exception('Order payment not found');
        }
        if ($orderPayment->status != 'initiated') {
            throw new \Exception('Order payment not initiated');
        }
        $orderPayment->status = 'abandoned';
        $orderPayment->save();
    }

    public function failedPayment($ref)
    {
        $orderPayment = EcommercePayment::where('reference', $ref)->first();
        if (! $orderPayment) {
            throw new \Exception('Order payment not found');
        }
        if ($orderPayment->status != 'initiated') {
            throw new \Exception('Order payment not initiated');
        }
        $orderPayment->status = 'failed';
        $orderPayment->save();
    }

    public function removeBoughtItemFromShoppingList($orderItems)
    {
        for ($i = 0; $i < count($orderItems); $i++) {
            EcommerceShopingList::where('user_id', Auth::id())->where('product_id', $orderItems[$i]->ecommerce_product_id)->delete();
        }
    }

    public function completeMandateSetup($data)
    {

        $body = $data->data;
        $reference = $body->reference;

        $this->fincraMandateRepository->verifyMandateStatus($reference);

    }

    public function resolveTransaction($data)
    {

        $body = $data->data;
        $merchantReference = $body->merchantReference;

        if(Str::startsWith($merchantReference, "PAY")){
            return $this->completeOrder($data);
        }elseif(Str::startsWith($merchantReference, "THG")){
            return $this->lenderDashboardRepository->completeWalletDeposit($data);
        }elseif(Str::startsWith($merchantReference, "LNR")){
            return $this->repaymentScheduleRepository->verifyRepaymentPayment($data);
        }

    }

    public function verifyFincraPaymentWebhook(Request $request)
    {
        $merchantWebhookSecretKey = config('services.fincra.secret');
        $payload = $request->getContent();

        // Generate the HMAC using SHA512
        $encryptedData = hash_hmac('sha512', $payload, $merchantWebhookSecretKey);

        // Get the signature from the request headers
        $signatureFromWebhook = $request->header('signature');

        // Compare the generated HMAC with the signature from the webhook
        if ($encryptedData === $signatureFromWebhook) {

            $data = json_decode($payload);

            $event = $data->event;

            switch ($event) {
                case 'charge.successful':
                    $this->resolveTransaction($data);
                    break;
                case 'mandate.approved':
                    $this->completeMandateSetup($data);
                    break;
                case 'direct_debit.success':
                    $this->fincraMandateRepository->completeDirectDebitRequest($data->data);
                    break;
                case 'payout.successful':
                    $this->fincraMandateRepository->completeDirectDebitRequest($data->data);
                    break;
                case 'payout.failed':
                    $this->fincraMandateRepository->completeDirectDebitRequest($data->data);
                    break;
                case 'charge.failed':
                    $this->failedPayment($data->data->merchantReference);
                    break;
                default:

                    break;
            }

        } else {


        }
    }

    public function mockFincraSuccessResponse($customer, $amount, $reference)
    {

        //generate random transaction id
        $transactionId = rand(10000, 99999);

        $sampleResponse = ["event"=> "charge.successful", "data" => [
            "id"=> $transactionId,
            "_id"=> "$transactionId",
            "vat"=> 9,
            "payer"=> $customer,
            "status"=> "successful",
            "message"=> null,
            "traceId"=> "",
            "metadata"=> null,
            "createdAt"=> Carbon::now(),
            "payeeName"=> "",
            "reference"=> "fcr-c-$transactionId",
            "settledAt"=> null,
            "sourceFee"=> 127.32,
            "updatedAt"=> Carbon::now(),
            "businessId"=> 7219,
            "isReversed"=> 0,
            "refundInfo"=> null,
            "description"=> null,
            "initiatedAt"=> Carbon::now(),
            "merchant_id"=> 5051,
            "isSubAccount"=> 0,
            "sourceAmount"=> $amount,
            "paymentMethod"=> "card",
            "approvalStatus"=> "approved",
            "destinationFee"=> 127.32,
            "sourceCurrency"=> "NGN",
            "mongoBusinessId"=> null,
            "payout_reference"=> null,
            "requiresApproval"=> 0,
            "virtualAccountId"=> null,
            "destinationAmount"=> $amount,
            "merchantReference"=> "$reference",
            "payeeAccountNumber"=> "",
            "destinationCurrency"=> "NGN",
            "sourceAmountSettled"=> $amount-127.32,
            "reversal_retry_count"=> 0,
            "settlementDestination"=> "wallet",
            "destinationAmountSettled"=> $amount-127.32,
            "electronicMoneyTransferLevy"=> 0
        ]];

        return json_decode(json_encode($sampleResponse));
    }

    public function mockFincraDebitMandateSuccess($debitMandate)
    {

        $sampleResponse = [
            "event"=> "mandate.approved",
            "data"=> [
                "amount"=> 5000,
                "description"=> "let the test begin",
                "responseDescription"=> "",
                "startDate"=> "2024-04-06T00:00:00.000Z",
                "endDate"=> "2024-04-30T00:00:00.000Z",
                "status"=> "approved",
                "reference"=> "mr_01bc59a4-32d6-45e2-9291-e8b2c64b6cfd",
                "createdAt"=> "2024-04-04T12:06:29.055Z"
                ]
            ];

        return json_decode(json_encode($sampleResponse));
    }
}
