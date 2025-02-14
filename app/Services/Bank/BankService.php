<?php

namespace App\Services\Bank;

use Illuminate\Http\Request;

class BankService
{

    public function getBankList()
    {
        try {



            $curl = curl_init();

            curl_setopt_array($curl, [
            CURLOPT_URL => config('services.fincra.url')."/core/banks?currency=NGN&country=NG",
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

            curl_close($curl);

            if ($err) {
                throw new \Exception($err);
            } else {
                $data = json_decode($response);
                return $data;
            }

        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function verifyBankAccount(Request $request)
    {
        try {

            $accountNumber = $request->accountNumber;
            $bankCode = $request->bankCode;

            $curl = curl_init();

            curl_setopt_array($curl, [
            CURLOPT_URL => config('services.fincra.url')."/core/accounts/resolve",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode([
                'accountNumber' => $accountNumber,
                'type' => 'nuban',
                'bankCode' => $bankCode
            ]),
            CURLOPT_HTTPHEADER => [
                "accept: application/json",
                "api-key: ".config('services.fincra.secret'),
                "content-type: application/json"
            ],
            ]);

            $response = curl_exec($curl);
            $err = curl_error($curl);

            curl_close($curl);

            if ($err) {
            // echo "cURL Error #:" . $err;
                throw new \Exception($err);
            } else {
                return json_decode($response);
            }

        } catch (\Throwable $th) {
            throw $th;
        }
    }

}
