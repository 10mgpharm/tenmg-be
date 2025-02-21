<?php

namespace App\Repositories;

use Illuminate\Http\Request;

class FincraMandateRepository
{

    public function generateMandateForCustomerClientMain(Request $request)
    {

        try {

            $body = json_encode([
                'currency' => 'NGN',
                'customer' => [
                    'accountNumber' => $request->customerAccountNumber,
                    'accountName' => $request->customerAccountName,
                    'address' => $request->customerAccountNumber,
                    'bankCode' => $request->customerBankCode,
                    'email' => $request->customer->email,
                    'phone' => $request->customer->phone
                ],
                'amount' => $request->amount,
                'description' => 'debit_mandate',
                'startDate' => $request->startDate,
                'endDate' => $request->endDate
            ]);

            $url = config('services.fincra.url')."/v2/mandate-mgt/mandates/";

            $curl = curl_init();

            curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => [
                "accept: application/json",
                "content-type: application/json",
                "api-key: ".config('services.fincra.secret'),
            ],
            ]);

            $response = curl_exec($curl);
            $err = curl_error($curl);
            $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);


            curl_close($curl);

            if ($err) {
                throw new \Exception($err);
            } else {
                if ($statusCode == 200) {
                    return json_decode($response);
                }
                $data = json_decode($response, true);

                if($data['message'] == "no Route matched with those values"){
                    throw new \Exception("No response from Fincra");
                }

            }

        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function verifyMandateStatus($reference)
    {

        try {

            $url = config('services.fincra.url')."/v2/mandate-mgt/mandates/reference/".$reference;


            $curl = curl_init();

            curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => [
                "accept: application/json",
                "api-key: ".config('services.fincra.secret')
            ],
            ]);

            $response = curl_exec($curl);
            $err = curl_error($curl);

            $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

            curl_close($curl);

            if ($err) {
                throw new \Exception($err);
            } else {
                if ($statusCode == 200) {
                    return json_decode($response);
                }
                $data = json_decode($response, true);

                if($data['message'] == "no Route matched with those values"){
                    throw new \Exception("No response from Fincra");
                }
            }

        } catch (\Throwable $th) {
            throw $th;
        }

    }



}
