<?php

namespace App\Repositories;

use App\Helpers\UtilityHelper;
use App\Models\Business;
use App\Models\CreditLendersWallet;
use App\Models\CreditLenderTxnHistory;
use App\Models\CreditOffer;
use App\Models\DebitMandate;
use App\Models\Loan;
use App\Models\LoanApplication;
use App\Models\RepaymentSchedule;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\OfferService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FincraMandateRepository
{

    function __construct(private OfferRepository $offerRepository, private LoanRepository $loanRepository, private RepaymentScheduleRepository $repaymentScheduleRepository, private NotificationService $notificationService) {

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
                'amount' => $request->amount/$request->duration,
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

            $debitMandate = DebitMandate::where('reference', $reference)->first();

            //check if mandate exist
            if(!$debitMandate){
                throw new \Exception("Mandate not found");
            }

            //check if mandate has been approved
            if($debitMandate->status == 'approved'){
                throw new \Exception("Mandate already approved");
            }

            if (config('app.env') != 'production') {
                $debitMandate->status = 'approved';
                $debitMandate->save();

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

                $debitMandate->status = 'approved';
                $debitMandate->save();

                $this->completeLoanApplication($debitMandate->application_id);
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
        })->whereHas('getLenderPreferences', function ($query) use ($customerCategory, $loanDuration) {
            $query->whereRaw('JSON_CONTAINS(credit_score_category, ?)', [json_encode($customerCategory)])->whereRaw('JSON_CONTAINS(loan_tenure, ?)', [$loanDuration]);
        })->get();

        if ($lendersBusinesses->isEmpty()) {
            $this->sendMailToLendersManualApproval($loanApplication);
            return;
        }

        $lastSelected = CreditOffer::first();

        if ($lastSelected) {
            $currentIndex = $lendersBusinesses->search(fn($lender) => $lender->id == $lastSelected->lender_id);
            $nextIndex = $currentIndex === false || $currentIndex === $lendersBusinesses->count() - 1 ? 0 : $currentIndex + 1;
        } else {
            $nextIndex = 0; // First lender
        }

        $selectedLender = $lendersBusinesses[$nextIndex];

        $offer = $this->createOffer($loanApplication, $selectedLender);
        $loan = $this->createLoan($offer, $loanApplication);

    }

    public function createOffer($loanApplication, $lender)
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
            'application_id' => $loanApplication->id,
            'offer_amount' => $amount,
            'repayment_breakdown' => $repaymentBreakdown,
            'has_mandate' => true,
            'accepted_at' => Carbon::now(),
            'lender_id' => $lender->id,
            'is_valid' => true,
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

        //debit lender deposit wallet
        $lenderWallet = CreditLendersWallet::where('lender_id', $offer->lender_id)->where('type', 'deposit')->first();
        $lenderWallet->prev_balance = $lenderWallet->current_balance;
        $lenderWallet->current_balance -= $offer->offer_amount;
        $lenderWallet->save();

        // Create the loan record
        $loan = $this->loanRepository->updateOrCreate([
            'business_id' => $loanApplication->business_id,
            'customer_id' => $loanApplication->customer_id,
            'application_id' => $loanApplication->id,
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

        $vendorBusiness = Business::where('id', $loan->business_id)->first();

        //send notification to customer
        $subject = 'Loan Application Approved';
        $message = "Your credit has been paid you can proceed to confirm your order from the vendor ".$vendorBusiness->name;
        $this->notificationService->sendCustomerNotification($loanApplication->customer_id, $subject, $message);

        //send notification to vendor
        $user = User::where('id', $vendorBusiness->owner_id)->first();
        $message = "Your customer".$loanApplication->customer->name." has been approved you can proceed to confirm the order from the customer";
        $vendorBusiness->owner->sendOrderConfirmationNotification($message, $user);

        //send notification to lender
        $message = "A loan request has been approved and disbursed to a customer. You can view the loan details on your dashboard";
        $lenderBusiness = Business::where('id', $offer->lender_id)->first();
        $user = User::where('id', $lenderBusiness->owner_id)->first();
        $lenderBusiness->owner->sendOrderConfirmationNotification($message, $user);

        $this->createDisbursementRecord($loan, $offer->lender_id);

    }

    public function createDisbursementRecord($loan, $lenderId)
    {
        //create disbursement record using
        DB::table('credit_disbursements')->updateOrInsert([
            'identifier' => UtilityHelper::generateSlug('LND'),
            'business_id' => $loan->business_id,
            'customer_id' => $loan->customer_id,
            'application_id' => $loan->application_id,
            'loan_id' => $loan->id,
            'disbursed_amount' => $loan->total_amount,
            'voucher_code' => $loan->identifier,
            'status' => 'DISBURSED',
            'lender_id' => $lenderId
        ]);

        //approve the loan application
        $loanApplication = LoanApplication::where('id', $loan->application_id)->first();
        $loanApplication->status = 'APPROVED';
        $loanApplication->save();
    }

    public function sendMailToLendersManualApproval($loanApplication)
    {
        $creditScore = $loanApplication->customer->creditScore;

        $amount = $loanApplication->requested_amount;
        $customerCategory = $creditScore->category;
        $loanDuration = $loanApplication->duration_in_months;

        $lendersBusinesses = Business::whereHas('getLenderPreferences', function($query) {
            $query->where('auto_accept', false);
        })->where('type', 'LENDER')->whereHas('lendersWallet', function ($query) use ($amount) {
            $query->where('current_balance', '>', $amount);
        })->whereHas('getLenderPreferences', function ($query) use ($customerCategory, $loanDuration) {
            $query->whereRaw('JSON_CONTAINS(credit_score_category, ?)', [json_encode($customerCategory)])->whereRaw('JSON_CONTAINS(loan_tenure, ?)', [$loanDuration]);
        })->get();

        //send email to all found lenders
        for ($i=0; $i < count($lendersBusinesses); $i++) {

            $message = "A loan request has been made by a customer. You can proceed to approve the loan request from your dashboard";
            $user = User::where('id', $lendersBusinesses[$i]->owner_id)->first();
            $lendersBusinesses[$i]->owner->sendOrderConfirmationNotification($message, $user);

        }

    }

    public function debitCustomerMandate($applicationId)
    {

        $mandateData = DebitMandate::where('application_id', $applicationId)->first();

        if(!$mandateData){
            throw new \Exception("Mandate not found");
        }

        $curl = curl_init();

        curl_setopt_array($curl, [
        CURLOPT_URL => config('services.fincra.url')."/mandate-mgt/mandates/payment",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => json_encode([
            'currency' => 'NGN',
            'amount' => $mandateData->amount,
            'description' => $mandateData->description,
            'mandateReference' => $mandateData->reference,
            'reference' => $mandateData->identifier,
            'bankCode' => $mandateData->customer_bank_code,
            'accountNumber' => $mandateData->customer_account_number,
            'initiatorAccountName' => $mandateData->customer_account_name,
            'beneficiaryNarration' => $mandateData->description

        ]),
        CURLOPT_HTTPHEADER => [
            "accept: application/json",
            "content-type: application/json"
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
                $data = json_decode($response);
                $this->completeDirectDebitRequest($data);
            }
            $data = json_decode($response, true);

            if($data['message'] == "no Route matched with those values"){
                throw new \Exception("No response from Fincra");
            }

        }

    }

    public function completeDirectDebitRequest($data)
    {

        $mandateData = DebitMandate::where('application_id', $data->reference)->first();

        if(!$mandateData){
            throw new \Exception('Mandate not found');
        }

        //amount debited
        $amount = $data->amount;
        $status = $data->status;

        //get the loan application id from the mandate
        $applicationId = $mandateData->application_id;

        //get loan id for the application
        $loanId = Loan::where('application_id', $applicationId)->first()->id;

        //get loan payment schedule
        $paymentSchedule = RepaymentSchedule::where('loan_id', $loanId)->where('payment_status', 'PENDING')->first();

        //Change the payment status to SUCCESS
        $paymentSchedule->payment_status = $status;
        $paymentSchedule->save();

        if($status == "initiated"){
            return "Debit mandate request is processing.";
        }

        //get lender business id from the loan offer
        $lenderBusinessId = CreditOffer::where('application_id', $applicationId)->first()->lender_id;

        //get the lender business for the loan
        $lenderBusiness = Business::find($lenderBusinessId);

        //get the lender business deposit wallet
        $lenderBusinessDepositWallet = $lenderBusiness->lendersWallet;
        $lenderBusinessDepositWallet->current_balance = $lenderBusinessDepositWallet->current_balance + $amount;
        $lenderBusinessDepositWallet->save();

        //Add to lender transaction history
        $lenderTxnHistory = CreditLenderTxnHistory::create([
            'amount' => $amount,
            'type' => 'Loan Repayment',
            'status' => 'success',
            'lender_id' => $lenderBusinessId,
            'transactionable_id' => $paymentSchedule->id,
            'transactionable_type' => "App\Models\RepaymentSchedule",
            'description' => 'Loan Repayment',
            'payment_schedule_id' => $paymentSchedule->id,
            'meta' => json_encode($data),
        ]);


    }

}
