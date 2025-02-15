<?php

namespace App\Services;

class DirectDebitMandateService
{
    public function createMandate(array $mandateRequest)
    {
        try {
            $payload = [
                'amount' => $mandateRequest['amount'],
                'description' => $mandateRequest['description'],
                'startDate' => $mandateRequest['startDate'],
                'endDate' => $mandateRequest['endDate'],
                'businessAddress' => $mandateRequest['businessAddress'],
                'customer' => [
                    'accountNumber' => $mandateRequest['accountNumber'],
                    'accountName' => $mandateRequest['accountName'],
                    'bankCode' => $mandateRequest['bankCode'],
                    'address' => $mandateRequest['address'],
                    'email' => $mandateRequest['email'],
                    'phone' => $mandateRequest['phone'],
                ],
                'currency' => $mandateRequest['currency'] ?? 'NGN',
            ];

            $curl = curl_init();

            curl_setopt_array($curl, [
                CURLOPT_URL => config('services.fincra.url').'/v2/mandate-mgt/mandates',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode($payload),
                CURLOPT_HTTPHEADER => [
                    'accept: application/json',
                    'api-key: '.config('services.fincra.secret'),
                    'x-business-id: '.config('services.fincra.secret'),
                    'content-type: application/json',
                ],
            ]);

            $response = curl_exec($curl);
            $err = curl_error($curl);

            curl_close($curl);

            if ($err) {
                throw new \Exception($err);
            } else {
                return json_decode($response);
            }

        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
