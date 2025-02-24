<?php

namespace App\Repositories;

use App\Helpers\UtilityHelper;
use App\Models\Business;
use App\Models\DebitMandate;
use App\Models\LoanApplication;
use App\Services\OfferService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class FincraMandateRepository
{

    function __construct(private OfferRepository $offerRepository, private LoanRepository $loanRepository, private RepaymentScheduleRepository $repaymentScheduleRepository) {

    }

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

            // return $this->completeLoadApplication();

            $debitMandate = DebitMandate::where('reference', $reference)->first();

            if (config('app.env') != 'production') {

                $this->completeLoanApplication($debitMandate->application_id);

                $mandateStatus =  [
                    'amount' => (int)$debitMandate->amount,
                    'description' => $debitMandate->description,
                    'responseDescription' => '',
                    'startDate' => $debitMandate->start_date,
                    'endDate' => $debitMandate->end_date,
                    'status' => $debitMandate->status,
                    'reference' => $debitMandate->reference,
                    'createdAt' => $debitMandate->created_at
                ];

                return $mandateStatus;
            }

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

    public function completeLoanApplication($applicationId)
    {


        //get the loan application
        $loanApplication = LoanApplication::where('id', $applicationId)->first();
        if(!$loanApplication){
            throw new \Exception("Loan application not found");
        }

        //check if user has an approved mandate
        $mandate = DebitMandate::where('status', 'approved')->where('application_id', $applicationId)->first();
        if(!$mandate){
            throw new \Exception("No approved mandate found");
        }

        //get customer most recent credit score evaluation
        $creditScore = $loanApplication->customer->creditScore;
        if(!$creditScore){
            throw new \Exception("No credit score evaluation found");
        }

        $amount = $loanApplication->requested_amount;
        $customerCategory = $creditScore->category;
        $loanDuration = $loanApplication->duration_in_months;

        $lendersBusinesses = Business::whereHas('getLenderPreferences', function($query) {
            $query->where('auto_accept', true);
        })->where('type', 'LENDER')->whereHas('lendersWallet', function ($query) use ($amount) {
            $query->where('current_balance', '>', $amount);
        })->whereHas('creditLendersPreference', function ($query) use ($customerCategory, $loanDuration) {
            $query->whereRaw('JSON_CONTAINS(credit_score_category, ?)', [json_encode($customerCategory)])->whereRaw('JSON_CONTAINS(loan_tenure, ?)', [$loanDuration]);
        })->get();

        $offer = $this->createOffer($loanApplication);
        $loan = $this->createLoan($offer, $loanApplication);

        //create disbursement record
        //debit from lender wallet

        return $loan;

    }

    public function createOffer($loanApplication)
    {


        $amount = $loanApplication->requested_amount;

        //generate repayment breakdown.
        $repaymentBreakdown = UtilityHelper::generateRepaymentBreakdown(
            $amount,
            $loanApplication->interest_rate,
            $loanApplication->duration_in_months
        );

        // Create offer
        $offer = $this->offerRepository->updateOrCreate([
            'customer_id' => $loanApplication->customer_id,
            'business_id' => $loanApplication->business_id,
            'application_id' => $loanApplication->applicationId,
            'offer_amount' => $amount,
            'repayment_breakdown' => $repaymentBreakdown,
            'has_mandate' => true,
            'accepted_at' => Carbon::now(),
        ]);

        return $offer;

    }

    public function createLoan($offer, $loanApplication)
    {

        $repaymentBreakdown = json_decode($offer->repayment_breakdown, true);

        $interestData = UtilityHelper::calculateInterestAmount(
            amount: $offer->offer_amount,
            durationInMonths: $offer?->application?->duration_in_months
        );

        $interestAmount = $offer->offer_amount * $interestData['monthlyInterestRate'] * $offer?->application?->duration_in_months;

        $loanData = [
            'business_id' => $offer->business_id,
            'customer_id' => $offer->customer_id,
            'application_id' => $offer->application_id,
            'offer_id' => $offer->id,
            'capital_amount' => $offer->offer_amount,
            'interest_amount' => $interestAmount,
            'total_amount' => $offer->offer_amount + $interestAmount,
            'status' => 'DISBURSED',
        ];

        // Create the loan record
        $loan = $this->loanRepository->updateOrCreate([
            'business_id' => $loanApplication->business_id,
            'customer_id' => $loanApplication->customer_id,
            'application_id' => $loanApplication->application_id,
        ], $loanData);

        // Generate repayment schedules
        foreach ($repaymentBreakdown as $schedule) {
            $this->repaymentScheduleRepository->store([
                'loan_id' => $loan->id,
                'total_amount' => $schedule['totalPayment'],
                'principal' => $schedule['principal'],
                'interest' => $schedule['interest'],
                'balance' => $schedule['balance'],
                'due_date' => Carbon::parse($schedule['month'])->endOfMonth(),
                'payment_status' => 'PENDING',
            ]);
        }

        return $loan;

    }

}
