<?php

namespace App\Services\Bank;

use App\Models\CreditCustomerBank;
use App\Models\Customer;
use Illuminate\Http\Request;

class BankService
{
    public function getBankList()
    {
        try {
            $curl = curl_init();

            curl_setopt_array($curl, [
                CURLOPT_URL => config('services.fincra.url').'/core/banks?currency=NGN&country=NG',
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

            if ($err) {
                throw new \Exception($err);
            } else {
                $data = json_decode($response);

                if ($data->success == false) {
                    return [];
                }

                return $data->data; // [{ "id", "code", "name", "isMobileVerified", "branches"},....]
            }

        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function getDefaultBank($businessId, Customer $customer): ?CreditCustomerBank
    {
        return CreditCustomerBank::where('customer_id', $customer->id)
            ->where('business_id', $businessId)
            ->where('is_default', 1)
            ->where('active', 1)
            ->first();
    }

    public function verifyBankAccount(Request $request)
    {
        try {
            $accountNumber = $request->accountNumber;
            $bankCode = $request->bankCode;

            $curl = curl_init();

            curl_setopt_array($curl, [
                CURLOPT_URL => config('services.fincra.url').'/core/accounts/resolve',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode([
                    'accountNumber' => $accountNumber,
                    'type' => 'nuban',
                    'bankCode' => $bankCode,
                ]),
                CURLOPT_HTTPHEADER => [
                    'accept: application/json',
                    'api-key: '.config('services.fincra.secret'),
                    'content-type: application/json',
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

    public function createBank(array $requestData): ?CreditCustomerBank
    {
        $businessId = $requestData['businessId'];

        $customer = Customer::where('business_id', $businessId)
            ->where(function ($query) use ($requestData) {
                $query->where('reference', $requestData['identifier'])
                    ->orWhere('identifier', $requestData['identifier']);
            })
            ->first();

        if (! $customer) {
            throw new \Exception('Customer not found for the selected business');
        }

        $defaultBank = $this->getDefaultBank($businessId, $customer);
        if ($defaultBank) {
            $defaultBank->is_default = 0;
            $defaultBank->save();
        }

        return CreditCustomerBank::firstOrCreate([
            'business_id' => $businessId,
            'customer_id' => $customer->id,
            'account_number' => $requestData['accountNumber'],
        ],
            [
                'business_id' => $businessId,
                'customer_id' => $customer->id,
                'account_number' => $requestData['accountNumber'],
                'account_name' => $requestData['accountName'],
                'bank_name' => $requestData['bankName'],
                'bank_code' => $requestData['bankCode'],
                'is_default' => 1,
            ]);
    }
}
