<?php

namespace App\Repositories;

use App\Jobs\TriggerWebhookJob;
use App\Models\ApiCallLog;
use App\Models\Business;
use App\Models\CreditTransactionHistory;
use App\Models\CreditTxnHistoryEvaluation;
use App\Models\Customer;
use App\Models\Loan;
use App\Models\LoanApplication;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VendorDashboardRepository
{

    public function getDashboardStats(Request $request)
    {

        $user = request()->user();
        $business_id = $user->ownerBusinessType?->id
            ?: $user->businesses()->firstWhere('user_id', $user->id)?->id;

        $customer = Customer::where('business_id', $business_id)->count();
        $applications = LoanApplication::where('business_id', $business_id)->count();
        $applicationsPending = LoanApplication::where('business_id', $business_id)->where('status', 'INITIATED')->count();
        $ongoingApplications = Loan::where('business_id', $business_id)->where('status', 'Ongoing')->count();
        $voucherWallet = Business::find($business_id);
        $transactionEvaluation = CreditTxnHistoryEvaluation::where('business_id', $business_id)->count();
        $stats = ApiCallLog::selectRaw('status, COUNT(*) as count')->where('business_id', $business_id)->groupBy('status')->pluck('count', 'status');

        return [
            'totalCustomers' => $customer,
            'totalApplications' => $applications,
            'totalPendingApplications' => $applicationsPending,
            'creditVoucher' => $voucherWallet->vendorsVoucherWallet?->current_balance ?? 0,
            'ongoingApplications' => $ongoingApplications,
            'payOutWallet' => $voucherWallet->vendorsPayoutWallet?->current_balance ?? 0,
            'transactionEvaluation' => $transactionEvaluation,
            'apiCalls' => ($stats['successful'] ?? 0) + ($stats['failed'] ?? 0),
            'accountLinking' => [
                'successfulCalls' => $stats['successful'] ?? 0,
                'errors' => $stats['failed'] ?? 0,
            ]
        ];

    }

    public function getGraphStats()
    {

        $now = Carbon::now();
        $year = $now->year;

        // Get loan counts grouped by month and status
        $loanStats = DB::table('credit_loans')
            ->selectRaw('MONTH(created_at) as month, status, COUNT(*) as total')
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', '<=', $now->month)
            ->whereIn('status', ['Ongoing', 'Completed']) // filter only needed statuses
            ->groupBy(DB::raw('MONTH(created_at)'), 'status')
            ->get();

            $formatted = [];

            foreach (range(1, $now->month) as $month) {
                $formatted[$month] = [
                    'month' => Carbon::create()->month($month)->format('F'),
                    'ongoing' => 0,
                    'completed' => 0,
                ];
            }

            foreach ($loanStats as $stat) {
                $formatted[$stat->month][$stat->status] = $stat->total;
            }

            //convert $formatted to array
            $formatted = array_values($formatted);

            return $formatted;

    }

}
