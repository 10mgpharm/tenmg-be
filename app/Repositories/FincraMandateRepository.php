<?php

namespace App\Repositories;

use App\Enums\InAppNotificationType;
use App\Helpers\UtilityHelper;
use App\Models\Business;
use App\Models\CreditLendersWallet;
use App\Models\CreditOffer;
use App\Models\CreditTransactionHistory;
use App\Models\CreditVendorWallets;
use App\Models\Customer;
use App\Models\DebitMandate;
use App\Models\Loan;
use App\Models\LoanApplication;
use App\Models\RepaymentSchedule;
use App\Models\TenMgWallet;
use App\Models\User;
use App\Notifications\Loan\LoanSubmissionNotification;
use App\Services\ActivityLogService;
use App\Services\AuditLogService;
use App\Services\InAppNotificationService;
use App\Services\NotificationService;
use App\Services\OfferService;
use App\Settings\LoanSettings;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

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

        //send notification to customer
        $subject = 'Your Credit Application Has Been Submitted';
        $message = "We have received your credit application and are currently reviewing it. You will be notified once a decision has been made.";
        $mailable = (new MailMessage)
            ->greeting('Hello '.$loanApplication->customer->name)
            ->subject($subject)
            ->line($message)
            ->line("Application ID: $loanApplication->identifier")
            ->line("Requested Amount: $amount")
            ->line("Submission Date: ".Carbon::now()->format('F jS, Y'))
            ->line("Thank you for choosing 10MG Credit.");
            $mailable->line('Best Regards,');
            $mailable->line('The 10MG Health Team');

        // notifation to customer here
        Notification::route('mail', [
            $loanApplication->customer->email => $loanApplication->customer->name,
        ])->notify(new LoanSubmissionNotification($mailable));

        //send notification to vendor
        $subject = 'Customer Applied for a Loan';
        $message = "Your customer, ".$loanApplication->customer->name.", has applied for a loan through 10MG Credit. Below are the details:";
        $vendorBusiness = Business::where('id', $loanApplication->business_id)->first();

        $mailable = (new MailMessage)
            ->greeting('Hello '.$vendorBusiness->owner->name)
            ->subject($subject)
            ->line($message)
            ->line("Customer Name: ".$loanApplication->customer->name)
            ->line("Application ID: $loanApplication->identifier")
            ->line("Requested Amount: $amount")
            ->line("Submission Date: ".Carbon::now()->format('F jS, Y'))
            ->line("We will notify you once the application is reviewed.");
            $mailable->line('Best Regards,');
            $mailable->line('The 10MG Health Team');

        // notifation to customer here
        Notification::route('mail', [
            $vendorBusiness->owner->email => $vendorBusiness->owner->name,
        ])->notify(new LoanSubmissionNotification($mailable));

        //send notification to admin
        $admins = User::role('admin')->get();
        for ($i=0; $i < count($admins); $i++) {
            $subject = 'New Loan Request Submitted';
            $message = "A new loan request has been submitted. Below are the details:";
            $mailable = (new MailMessage)
                ->greeting('Hello '.$admins[$i]->name)
                ->subject($subject)
                ->line($message)
                ->line("Customer Name: ".$loanApplication->customer->name)
                ->line("Vendor: ".$vendorBusiness->owner->name)
                ->line("Application ID: $loanApplication->identifier")
                ->line("Requested Amount: $amount")
                ->line("Submission Date: ".Carbon::now()->format('F jS, Y'))
                ->line('Best Regards,')
                ->line('The 10MG Health Team');

            Notification::route('mail', [
                $admins[$i]->email => $admins[$i]->name,
            ])->notify(new LoanSubmissionNotification($mailable));

        }
        (new InAppNotificationService)
            ->forUsers($admins)->notify(InAppNotificationType::NEW_LOAN_REQUEST);


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
            'status' => 'Ongoing',
        ];

        //debit lender deposit wallet
        $lenderWallet = CreditLendersWallet::where('lender_id', $offer->lender_id)->where('type', 'deposit')->first();
        $lenderWallet->prev_balance = $lenderWallet->current_balance;
        $lenderWallet->current_balance -= $offer->offer_amount;
        $lenderWallet->save();

        //add to lender transaction history
        CreditTransactionHistory::create([
            'amount' => $offer->offer_amount,
            'type' => 'DEBIT',
            'status' => 'success',
            'business_id' => $offer->lender_id,
            'description' => 'Loan disbursement to '.$offer->customer->name,
            'loan_application_id' => $offer->application_id,
            'transaction_group' => 'loan_disbursement',
            'wallet_id' => $lenderWallet->id,
            'meta' => json_encode($loanData),
        ]);

        //add the debited amount to the ledger wallet
        $ledgerWallet = CreditLendersWallet::firstOrNew([
            'lender_id' => $offer->lender_id,
            'type' => 'ledger'
        ]);

        $prevBalance = $ledgerWallet->exists ? $ledgerWallet->current_balance : 0;
        $ledgerWallet->prev_balance = $prevBalance;
        $ledgerWallet->current_balance = $prevBalance + $offer->offer_amount;
        $ledgerWallet->save();

        //add to ledger transaction history
        CreditTransactionHistory::create([
            'amount' => $offer->offer_amount,
            'type' => 'CREDIT',
            'status' => 'success',
            'business_id' => $offer->lender_id,
            'description' => 'Loan disbursement to '.$offer->customer->name,
            'loan_application_id' => $offer->application_id,
            'transaction_group' => 'loan_disbursement',
            // 'wallet_id' => $ledgerWallet->id,
            'meta' => json_encode($loanData),
        ]);

        //add amount to vendor voucherwallet
        $vendorWallet = CreditVendorWallets::where('vendor_id', $loanApplication->business_id)->where('type', 'credit_voucher')->first();
        $vendorWallet->prev_balance = $vendorWallet->current_balance;
        $vendorWallet->current_balance += $offer->offer_amount;
        $vendorWallet->save();

        //add to vendor transaction history
        CreditTransactionHistory::create([
            'amount' => $offer->offer_amount,
            'type' => 'CREDIT',
            'status' => 'success',
            'business_id' => $loanApplication->business_id,
            'description' => 'Loan voucher for '.$loanApplication->customer->name,
            'loan_application_id' => $loanApplication->id,
            'transaction_group' => 'loan_disbursement',
            // 'wallet_id' => $vendorWallet->id,
            'meta' => json_encode($loanData),
        ]);


        // Create the loan record
        $loan = $this->loanRepository->updateOrCreate([
            'business_id' => $loanApplication->business_id,
            'customer_id' => $loanApplication->customer_id,
            'application_id' => $loanApplication->id,
        ], $loanData);

        $scheduleForMail = [];
        $initTerm = 1;

        // Generate repayment schedules
        foreach ($repaymentBreakdown as $schedule) {
            $res = $this->repaymentScheduleRepository->store([
                'loan_id' => $loan->id,
                'total_amount' => $schedule['totalPayment'],
                'principal' => $schedule['principal'],
                'interest' => $schedule['interest'],
                'balance' => $schedule['balance'],
                'due_date' => Carbon::parse($schedule['month'])->endOfMonth(),
                'payment_status' => 'PENDING',
            ]);
            $scheduleForMail[] = "Repayment $initTerm: $res->total_amount - ".Carbon::parse($res->due_date)->format('F jS, Y');
            $initTerm++;
        }


        $this->sendLoanApprovalProcess($loanApplication, $offer, $loan, $scheduleForMail);


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
        })->where('type', 'LENDER')->whereHas('getLenderPreferences', function ($query) use ($customerCategory, $loanDuration) {
            $query->whereRaw('JSON_CONTAINS(credit_score_category, ?)', [json_encode($customerCategory)])->whereRaw('JSON_CONTAINS(loan_tenure, ?)', [$loanDuration]);
        })->get();

        //send email to all found lenders
        for ($i=0; $i < count($lendersBusinesses); $i++) {

            $message = "A loan request has been made by a customer. You can proceed to approve the loan request from your dashboard";
            $user = User::where('id', $lendersBusinesses[$i]->owner_id)->first();
            $subject = 'Customer Applied for a Loan';
            // $vendorBusiness = Business::where('id', $loanApplication->business_id)->first();
            $mailable = (new MailMessage)
                ->greeting('Hello '.$lendersBusinesses[$i]->owner->name)
                ->subject($subject)
                ->line($message)
                ->line("Customer Name: ".$loanApplication->customer->name)
                ->line("Application ID: $loanApplication->identifier")
                ->line("Requested Amount: $amount")
                ->line("Submission Date: ".Carbon::now()->format('F jS, Y'))
                ->line("We will notify you once the application is reviewed.");
                $mailable->line('Best Regards,');
                $mailable->line('The 10MG Health Team');

            Notification::route('mail', [
                $user->email => $user->name,
            ])->notify(new LoanSubmissionNotification($mailable));

            (new InAppNotificationService)
            ->forUser($user)->notify(InAppNotificationType::NEW_LOAN_REQUEST);

        }

    }

    public function debitCustomerMandate($applicationId)
    {

        $mandateData = DebitMandate::where('application_id', $applicationId)->first();

        if(!$mandateData){
            throw new \Exception("Mandate not found");
        }

        if(config('app.env') != 'production'){

            $data = $this->mockChargeMandate($mandateData);
            $this->completeDirectDebitRequest($data->data);
            return;

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

        $mandateData = DebitMandate::where('reference', $data->reference)->first();

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
        $paymentSchedules = RepaymentSchedule::where('loan_id', $loanId)->where('payment_status', 'PENDING')->get();

        //if we have pending payment schedules
        if($paymentSchedules->isEmpty()){
            throw new \Exception('No pending payment schedule found');
        }

        $paymentSchedule = $paymentSchedules->first();

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

        $totalInterest = $paymentSchedule->interest;
        $loanSettings = new LoanSettings();
        $lenderInterest = $loanSettings->lenders_interest;
        $tenmgInterest = $loanSettings->tenmg_interest;

        $tenmgInterestAmount = ($totalInterest * $tenmgInterest) / 100;
        $lenderInterestAmount = $totalInterest - $tenmgInterestAmount;
        $lenderTotalExcludingTenmgPercent = $paymentSchedule->principal + $lenderInterestAmount;

        //get the lender business investment wallet
        $lenderBusinessInvWallet = $lenderBusiness->lendersInvestmentWallet;
        $lenderBusinessInvWallet->current_balance = $lenderBusinessInvWallet->current_balance + $lenderTotalExcludingTenmgPercent;
        $lenderBusinessInvWallet->prev_balance = $lenderBusinessInvWallet->current_balance;
        $lenderBusinessInvWallet->save();

        //add to lender transaction history
        CreditTransactionHistory::create([
            'amount' => $lenderTotalExcludingTenmgPercent,
            'type' => 'CREDIT',
            'status' => 'success',
            'business_id' => $lenderBusinessId,
            'description' => 'Loan Repayment from '.$mandateData->customer->name,
            'loan_application_id' => $applicationId,
            'transaction_group' => 'repayment',
            'meta' => json_encode($data),
        ]);

        //add admin interest to admin wallet
        $adminWallet = TenMgWallet::firstOrCreate();
        $currentBalance = $adminWallet->current_balance ?? 0; // Handle NULL case

        $adminWallet->update([
            'current_balance' => $currentBalance + $tenmgInterestAmount,
            'previous_balance' => $currentBalance
        ]);

        //get 10mg admin business
        $adminBusiness = Business::where('type', 'ADMIN')->first();

        //add to admin transaction history
        CreditTransactionHistory::create([
            'amount' => $tenmgInterestAmount,
            'type' => 'CREDIT',
            'status' => 'success',
            'business_id' => $adminBusiness->id,
            'description' => 'Loan Repayment from '.$mandateData->customer->name,
            'loan_application_id' => $applicationId,
            'transaction_group' => 'repayment_commission',
            'meta' => json_encode($data),
        ]);

        //get the vendor for the loan
        $vendorBusiness = Business::where('id', $loanId->business_id)->first();
        $vendorCreditVoucherWallet = CreditVendorWallets::where('vendor_id', $vendorBusiness->id)->where('type', 'credit_voucher')->first();
        $vendorCreditVoucherWallet->prev_balance = $vendorCreditVoucherWallet->current_balance;
        $vendorCreditVoucherWallet->current_balance = $vendorCreditVoucherWallet->current_balance - $paymentSchedule->principal;
        $vendorCreditVoucherWallet->save();

        //add to vendor payout wallet
        $vendorPayoutWallet = CreditVendorWallets::where('vendor_id', $vendorBusiness->id)->where('type', 'payout')->first();
        $vendorPayoutWallet->prev_balance = $vendorPayoutWallet->current_balance;
        $vendorPayoutWallet->current_balance = $vendorPayoutWallet->current_balance + $paymentSchedule->principal;
        $vendorPayoutWallet->save();

        //add to vendor transaction history
        CreditTransactionHistory::create([
            'amount' => $paymentSchedule->principal,
            'type' => 'CREDIT',
            'status' => 'success',
            'business_id' => $vendorBusiness->id,
            'description' => 'Loan repayment from '.$mandateData->customer->name,
            'loan_application_id' => $applicationId,
            'transaction_group' => 'repayment',
            // 'wallet_id' => $vendorPayoutWallet->id,
            'meta' => json_encode($data),
        ]);//

        if(count($paymentSchedules) == 1){
            //update loan status to completed
            $loan = Loan::where('application_id', $applicationId)->first();
            $loan->status = 'Completed';
            $loan->save();
        }

    }

    public function sendLoanApprovalProcess($loanApplication, $offer, $loan, $scheduleForMail)
    {

        $vendorBusiness = Business::where('id', $loan->business_id)->first();

        Log::info("info", $scheduleForMail);

        //send notification to customer
        $subject = 'Your Loan Has Been Approved!';
        $message = "Good news! Your loan application has been approved. Below are the details:";
        $mailable = (new MailMessage)
            ->greeting('Hello '.$loanApplication->customer->name)
            ->subject($subject)
            ->line($message)
            ->line("Loan ID: $loanApplication->identifier")
            ->line("Approved Amount: $loan->total_amount")
            ->line("Below is the repayment Schedule:");

            for ($i = 0; $i < count($scheduleForMail); $i++) {
                $mailable->line($scheduleForMail[$i]);
            }
            $mailable->line('Best Regards,');
            $mailable->line('The 10MG Health Team');

        // notifation to customer here
        Notification::route('mail', [
            $loanApplication->customer->email => $loanApplication->customer->name,
        ])->notify(new LoanSubmissionNotification($mailable));

        //send notification to vendor
        $subject = 'Loan Approved for Your Customer';
        $user = User::where('id', $vendorBusiness->owner_id)->first();
        $message = "We are pleased to inform you that your customer, ".$loanApplication->customer->name." has been approved for a loan. Below are the details:";

        $mailable = (new MailMessage)
            ->greeting('Hello '.$user->name)
            ->subject($subject)
            ->line($message)
            ->line("Loan ID: $loanApplication->identifier")
            ->line("Approved Amount: $loan->total_amount")
            ->line("Below is the repayment Schedule:");

            for ($i = 0; $i < count($scheduleForMail); $i++) {
                $mailable->line($scheduleForMail[$i]);
            }
            $mailable->line('The customer is now eligible to complete their purchase.');
            $mailable->line('Best Regards,');
            $mailable->line('The 10MG Health Team');

        Notification::route('mail', [
            $user->email => $user->name,
        ])->notify(new LoanSubmissionNotification($mailable));

        (new InAppNotificationService)
            ->forUser($user)->notify(InAppNotificationType::NEW_LOAN_REQUEST);

        //send notification to lender
        $subject = 'Loan Request Approved';
        $message = "A loan request assigned to you has been automatically approved based on the configured settings. Below are the details:";
        $lenderBusiness = Business::where('id', $offer->lender_id)->first();
        $user = User::where('id', $lenderBusiness->owner_id)->first();
        $mailable = (new MailMessage)
            ->greeting('Hello '.$user->name)
            ->subject($subject)
            ->line($message)
            ->line("Customer Name: ".$loanApplication->customer->name)
            ->line("Vendor: ".$vendorBusiness->owner->name)
            ->line("Loan ID: $loanApplication->identifier")
            ->line("Approved Amount: $loan->total_amount");
            for ($i = 0; $i < count($scheduleForMail); $i++) {
                $mailable->line($scheduleForMail[$i]);
            }
            $mailable->line("Approval Date: ".Carbon::now()->format('F jS, Y'));
            $mailable->line('Best Regards,');
            $mailable->line('The 10MG Health Team');

        Notification::route('mail', [
            $user->email => $user->name,
        ])->notify(new LoanSubmissionNotification($mailable));

        (new InAppNotificationService)
            ->forUser($user)->notify(InAppNotificationType::NEW_LOAN_REQUEST);


        //send mails to all admins
        //get users with role admin
        $admins = User::role('admin')->get();
        for ($i=0; $i < count($admins); $i++) {
            $subject = 'Lender Approved Customer Credit';
            $message = "A loan application has been approved for a customer. Below are the details:";
            $mailable = (new MailMessage)
                ->greeting('Hello '.$admins[$i]->name)
                ->subject($subject)
                ->line($message)
                ->line("Customer Name: ".$loanApplication->customer->name)
                ->line("Vendor: ".$vendorBusiness->owner->name)
                ->line("Loan ID: $loanApplication->identifier")
                ->line("Approved Amount: $loan->total_amount")
                ->line("Lender: ".$lenderBusiness->owner->name)
                ->line("Approval Date: ".Carbon::now()->format('F jS, Y'))
                ->line('Best Regards,')
                ->line('The 10MG Health Team');

            Notification::route('mail', [
                $admins[$i]->email => $admins[$i]->name,
            ])->notify(new LoanSubmissionNotification($mailable));
        }
        (new InAppNotificationService)
            ->forUsers($admins)->notify(InAppNotificationType::LOAN_REQUEST_APPROVED);

        $customer = Customer::where('id', $loanApplication->customer_id)->first();

        AuditLogService::log(
            target: $loanApplication,
            event: 'Loan.initiated',
            action: 'Loan application Initiated',
            description: $lenderBusiness->name." approved ".$customer->name."  of ".$vendorBusiness->name." loan application.",
            crud_type: 'UPDATE',
            properties: []
        );

    }

    public function mockChargeMandate($mandate)
    {
        $transactionId = rand(10000, 99999);

        $mandateSample = [
            "event"=> "charge.successful",
            "data"=> [
              "chargeReference"=> "fcr-bt-$transactionId",
              "amountToSettle"=> $mandate->amount,
              "id"=> $transactionId,
              "authorization"=> [
                "mode"=> null,
                "redirect"=> null,
                "metadata"=> null
              ],
              "auth_model"=> null,
              "amount"=> $mandate->amount,
              "amountExpected"=> $mandate->amount,
              "amountReceived"=> $mandate->amount,
              "varianceType"=> null,
              "currency"=> "NGN",
              "fee"=> 1.51,
              "vat"=> 0.11,
              "message"=> "",
              "actionRequired"=> null,
              "status"=> "success",
              "reference"=> $mandate->reference,
              "description"=> "checkout",
              "type"=> "bank_transfer",
              "customer"=> [
                "name"=> $mandate->customer_account_name,
                "email"=> $mandate->customer_email,
                "phoneNumber"=> $mandate->customer_phone
            ],
              "metadata"=> [],
              "settlementDestination"=> "wallet",
              "settlementTime"=> "instant",
              "virtualAccount"=> [
                "bankName"=> $mandate->amount,
                "id"=> "6645e0h8s783h8s0ee8f673",
                "bankCode"=> "103",
                "accountName"=> "Fincra DevRel",
                "accountNumber"=> "39973787851",
                "sessionId"=> "ETZ-09F87348787388OHT",
                "channelName"=> "globus",
                "payerAccountNumber"=> "1228939338",
                "payerAccountName"=> " Fincra DevRel",
                "payerBankName"=> "Access Bank PLC",
                "payerBankCode"=> "044",
                "expiresAt"=> "2024-05-16T10:51:36.000Z",
                "business"=> "64f1c939hhsu993sf4a710"
            ]
            ]
              ];

              return json_decode(json_encode($mandateSample));
    }

}
