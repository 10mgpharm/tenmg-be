<?php

namespace App\Repositories;

use App\Models\Business;
use App\Models\CreditOffer;
use App\Models\CreditRepaymentPayments;
use App\Models\CreditTransactionHistory;
use App\Models\CreditVendorWallets;
use App\Models\DebitMandate;
use App\Models\Loan;
use App\Models\LoanApplication;
use App\Models\RepaymentPayments;
use App\Models\RepaymentSchedule;
use App\Models\TenMgWallet;
use App\Settings\LoanSettings;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RepaymentScheduleRepository
{
    public function store(array $data)
    {
        return RepaymentSchedule::create($data);
    }

    // fetch repayment schedule by loan id
    public function fetchRepaymentScheduleByLoanId(int $loanId): Collection
    {
        return RepaymentSchedule::where('loan_id', $loanId)->get();
    }

    // fetch repayment schedule by cusstomer id
    public function fetchRepaymentScheduleByCustomerId(int $customerId): Collection
    {
        //credit_loans
        return RepaymentSchedule::whereHas('loan', function ($query) use ($customerId) {
            $query->where('customer_id', $customerId);
        })->get();
    }

    // fetch repayment schedule by id
    public function fetchRepaymentScheduleById($id): RepaymentSchedule
    {
        return RepaymentSchedule::find($id);
    }

    // update repayment schedule by id
    public function updateRepaymentScheduleById($id, array $data): bool
    {
        return RepaymentSchedule::where('id', $id)->update($data);
    }

    // delete repayment schedule by id
    public function deleteRepaymentScheduleById($id)
    {
        return RepaymentSchedule::where('id', $id)->delete();
    }

    // delete repayment schedule by loan id
    public function deleteRepaymentScheduleByLoanId($loanId)
    {
        return RepaymentSchedule::where('loan_id', $loanId)->delete();
    }

    /**
     * Get repayment schedules due on a specific date.
     */
    public function getRepaymentsDueOnDate(Carbon $dueDate)
    {
        return RepaymentSchedule::whereDate('due_date', $dueDate)
            ->where('payment_status', 'PENDING')
            ->with(['loan.customer']) // eager load related customer and loan info
            ->get();
    }

    public function findProcessingRepayments(): Collection
    {
        return RepaymentSchedule::where('payment_status', 'PROCESSING')->get();
    }

    public function markRepaymentsAsCancelled(int $loanId): bool
    {
        return RepaymentSchedule::where('loan_id', $loanId)
            ->where('payment_status', 'PENDING')
            ->orWhere('payment_status', 'PROCESSING')
            ->update(['payment_status' => 'CANCELLED']);
    }

    public function processRepaymentForLoan(int $loanId)
    {
        $latestRepayment = RepaymentSchedule::where('loan_id', $loanId)
            ->where('payment_status', 'PENDING')
            ->orderBy('id', 'asc')
            ->first();

        //check if we are on production or test
        if (config('app.env') != 'production') {

            $latestRepayment->payment_status = 'PAID';
            $latestRepayment->save();



        }
    }

    public function getLoanByReference($reference)
    {
        $loan = Loan::where('identifier', $reference)->first();
        if (!$loan) {
            throw new \Exception('Loan not found');
        }

        // Check if the loan is already paid
        if ($loan->status === 'Completed') {
            throw new \Exception('Loan is already paid');
        }

        return $loan;
    }

    public function initiateRepayment(Request $request)
    {

        $loan = Loan::where("identifier", $request->reference)->first();
        $initData = null;

        if($request->paymentType == "fullPayment"){
            $initData = $this->calculateFullPayment($request, $loan);
        }else{
            $initData = $this->calculatePartPayment($request, $loan);
        }

        return $initData;
    }

    public function calculateFullPayment($request, $loan)
    {

        //check if we have repayment payment not successful
        $repaymentPayment = CreditRepaymentPayments::where('loan_id', $loan->id)->where('status', '!=', 'success')->where('status', '!=', 'abandoned')->first();
        if ($repaymentPayment) {
            return [
                'amount' => $repaymentPayment->amount,
                'loan' => $loan,
                'init' => $repaymentPayment
            ];
        }

        $repayment = RepaymentSchedule::where('loan_id', $loan->id)->where('payment_status', 'PENDING')->sum('total_amount');

        if ($repayment < 1) {
            //check if we have repayment in processing
            $repaymentInProcessing = RepaymentSchedule::where('loan_id', $loan->id)->where('payment_status', 'PROCESSING')->sum('total_amount');
            if ($repaymentInProcessing > 0) {
                throw new \Exception('We are already processing your repayment');
            }

            throw new \Exception('No pending loan repayment');
        }

        $addRepaymentPayment = CreditRepaymentPayments::create(
            [
                'loan_id' => $loan->id,
                'amount' => $repayment,
                'fee' => 0,
                'total_amount' => $repayment,
                'status' => 'initiated',
                'reference' => $loan->identifier,
                'channel' => 'fincra',
                'business_id' => $loan->business_id,
                'customer_id' => $loan->customer_id,
            ]
        );

        if ($addRepaymentPayment) {
            RepaymentSchedule::where('loan_id', $loan->id)->where('payment_status', 'PENDING')->update(['payment_id' => $addRepaymentPayment->id]);
        }

        return [
            'amount' => $repayment,
            'loan' => $loan,
            'init' => $addRepaymentPayment
        ];

    }

    public function calculatePartPayment($request, $loan)
    {
        //check if we have repayment payment not successful
        $repaymentPayment = CreditRepaymentPayments::where('loan_id', $loan->id)->where('status', '!=', 'success')->where('status', '!=', 'abandoned')->first();
        if ($repaymentPayment) {
            return [
                'amount' => $repaymentPayment->amount,
                'loan' => $loan,
                'init' => $repaymentPayment
            ];
        }

        $noOfMonths = $request->noOfMonths;
        $noOfRepaymentsRemaining = RepaymentSchedule::where('loan_id', $loan->id)->where('payment_status', 'PENDING')->count();
        if ($noOfMonths > $noOfRepaymentsRemaining) {
            throw new \Exception('You cannot pay more than the number of repayments remaining');
        }

        //check if we have repayment in processing
        $repaymentInProcessing = RepaymentSchedule::where('loan_id', $loan->id)->where('payment_status', 'PROCESSING')->get();
        if (count($repaymentInProcessing) > 0) {
            throw new \Exception('We are already processing your repayment(s)');
        }

        $repayment = RepaymentSchedule::where('loan_id', $loan->id)->where('payment_status', 'PENDING')->orderBy('due_date', 'asc')->take($noOfMonths)->get()->sum('total_amount');

        $addRepaymentPayment = CreditRepaymentPayments::create(
            [
                'loan_id' => $loan->id,
                'amount' => $repayment,
                'fee' => 0,
                'total_amount' => $repayment,
                'status' => 'initiated',
                'reference' => $loan->identifier,
                'channel' => 'fincra',
                'business_id' => $loan->business_id,
                'customer_id' => $loan->customer_id,
            ]
        );

        if ($addRepaymentPayment) {
            RepaymentSchedule::where('loan_id', $loan->id)->where('payment_status', 'PENDING')->orderBy('due_date', 'asc')->take($noOfMonths)->update(['payment_id' => $addRepaymentPayment->id]);
        }

        return [
            'amount' => $repayment,
            'loan' => $loan,
            'init' => $addRepaymentPayment
        ];
    }

    public function cancelPayment($paymentRef)
    {
        $repaymentPayment = CreditRepaymentPayments::where('reference', $paymentRef)->first();
        if (!$repaymentPayment) {
            throw new \Exception('Payment not found');
        }
        if ($repaymentPayment->status != 'initiated') {
            throw new \Exception('Payment cannot be cancelled at this time');
        }

        $repaymentPayment->status = 'abandoned';
        $repaymentPayment->save();

        //detach payment from repayment schedule
        RepaymentSchedule::where('payment_id', $repaymentPayment->id)->update(['payment_id' => null]);

        return $repaymentPayment;
    }

    public function verifyRepaymentPayment($data)
    {

        $body = $data->data;
        $merchantReference = $body->merchantReference;

        $repaymentPayment = CreditRepaymentPayments::where('reference', $merchantReference)->first();

        if($repaymentPayment->status == 'success'){
            return;
        }

        $repayments = RepaymentSchedule::where('payment_id', $repaymentPayment->id)->get();
        $applicationId = null;
        $loanId = null;
        for ($i=0; $i < count($repayments); $i++) {
            $applicationId = $repayments[$i]->loan->application_id;
            $loanId = $repayments[$i]->loan_id;
            $this->completeDirectDebitRequest($body, $repayments[$i]);
        }

        $allPaidRepayments = RepaymentSchedule::where('loan_id', $loanId)->where('payment_status', "PAID")->get();
        $allRepayments = RepaymentSchedule::where('loan_id', $loanId)->get();

        $repaymentPayment->status = 'success';
        $repaymentPayment->save();

        if(count($allPaidRepayments) == count($allRepayments)){
            //update loan status to completed
            $loan = Loan::where('application_id', $applicationId)->first();
            $loan->status = 'Completed';
            $loan->save();
        }

    }


    public function completeDirectDebitRequest($data, $paymentSchedule)
    {

        // $mandateData = DebitMandate::where('reference', $data->reference)->first();

        // if(!$mandateData){
        //     throw new \Exception('Mandate not found');
        // }

        //get loan id for the application
        $loanRequest = Loan::where('id', $paymentSchedule->loan_id)->first();
        $loan = $loanRequest->id;

        //amount debited
        // $amount = $data->amount;
        $status = $data->status;

        //get the loan application id from loan
        $applicationId = $loanRequest->application_id;

        $loanApplication = LoanApplication::find($applicationId);

        //Change the payment status to SUCCESS
        $paymentSchedule->payment_status = "PAID";
        $paymentSchedule->save();

        if($status == "initiated"){
            return "Debit mandate request is processing.";
        }

        //get lender business id from the loan offer
        $lenderBusinessId = CreditOffer::where('application_id', $applicationId)->first()->lender_id;

        //get the lender business for the loan
        $lenderBusiness = Business::find($lenderBusinessId);
        $totalInterest = $paymentSchedule->interest;
        $tenmgInterest = $loanApplication->tenmg_interest;

        //get 10mg interest for one repayment in percent
        $oneMonthTenMgPercent = $tenmgInterest/$loanApplication->duration_in_months;

        $tenmgInterestAmount = ($totalInterest * $oneMonthTenMgPercent) / 100;
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
            'description' => 'Loan Repayment',
            'loan_application_id' => $applicationId,
            'transaction_group' => 'repayment',
            'meta' => json_encode($data),
        ]);

        //add admin interest to admin wallet
        TenMgWallet::updateOrCreate(
            ['id' => TenMgWallet::first()?->id],
            [
                'current_balance' => DB::raw("COALESCE(current_balance, 0) + $tenmgInterestAmount"),
                'previous_balance' => DB::raw('current_balance')
            ]
        );

        //get 10mg admin business
        $adminBusiness = Business::where('type', 'ADMIN')->first();

        //add to admin transaction history
        CreditTransactionHistory::create([
            'amount' => $tenmgInterestAmount,
            'type' => 'CREDIT',
            'status' => 'success',
            'business_id' => $adminBusiness->id,
            'description' => 'Loan Repayment',
            'loan_application_id' => $applicationId,
            'transaction_group' => 'repayment_commission',
            'meta' => json_encode($data),
        ]);

        //get the vendor for the loan
        $vendorBusiness = Business::where('id', $loanRequest->business_id)->lockForUpdate()->first();
        $vendorCreditVoucherWallet = CreditVendorWallets::where('vendor_id', $vendorBusiness->id)->where('type', 'credit_voucher')->lockForUpdate()->first();
        $vendorCreditVoucherWallet->prev_balance = $vendorCreditVoucherWallet->current_balance;
        $vendorCreditVoucherWallet->current_balance = $vendorCreditVoucherWallet->current_balance - $paymentSchedule->principal;
        $vendorCreditVoucherWallet->save();

        //add to vendor payout wallet
        $vendorPayoutWallet = CreditVendorWallets::where('vendor_id', $vendorBusiness->id)->where('type', 'payout')->lockForUpdate()->first();
        $vendorPayoutWallet->prev_balance = $vendorPayoutWallet->current_balance;
        $vendorPayoutWallet->current_balance = $vendorPayoutWallet->current_balance + $paymentSchedule->principal;
        $vendorPayoutWallet->save();

        //add to vendor transaction history
        CreditTransactionHistory::create([
            'amount' => $paymentSchedule->principal,
            'type' => 'CREDIT',
            'status' => 'success',
            'business_id' => $vendorBusiness->id,
            'description' => 'Loan repayment',
            'loan_application_id' => $applicationId,
            'transaction_group' => 'payout',
            // 'wallet_id' => $vendorPayoutWallet->id,
            'meta' => json_encode($data),
        ]);//



    }

    public function getListOfLoanRepayments(array $filters, int $perPage = 15):\Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        //get the business type
        $user = request()->user();
        $business = $user->ownerBusinessType
            ?: $user->businesses()->firstWhere('user_id', $user->id);

        if($business->type != "ADMIN"){
            $filters['businessId'] = $business->id;
        }

        $query = RepaymentSchedule::query();

        $query->when(isset($filters['search']), function ($query) use ($filters) {
            $searchTerm = "%{$filters['search']}%";
            return $query->whereHas('loan.customer', function ($query) use ($searchTerm) {
                $query->where('name', 'like', $searchTerm);
            });
        });

        $query->when(isset($filters['status']), function ($query) use ($filters) {
            return $query->where('payment_status', $filters['status']);
        });

        $query->when(
            isset($filters['dateFrom']) && isset($filters['dateTo']),
            function ($query) use ($filters) {
                // Parse dates with Carbon to ensure proper format
                $dateFrom = \Carbon\Carbon::parse($filters['dateFrom'])->startOfDay();
                $dateTo = \Carbon\Carbon::parse($filters['dateTo'])->endOfDay();

                return $query->whereBetween('due_date', [$dateFrom, $dateTo]);
            }
        );

        $query->when(isset($filters['businessId']), function ($query) use ($filters) {
            return $query->whereHas('loan.business', function ($query) use ($filters) {
                $query->where('business_id', $filters['businessId']);
            });
        });

        $query->orderBy('due_date', 'desc');

        return $query->paginate($perPage);

    }

}
