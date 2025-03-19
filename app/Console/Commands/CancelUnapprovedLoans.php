<?php

namespace App\Console\Commands;

use App\Models\LoanApplication;
use App\Notifications\Loan\LoanSubmissionNotification;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Notification;

class CancelUnapprovedLoans extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:cancel-unapproved-loans';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cancel loan requests that have not been approved within five(5) days.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $thresholdDate = Carbon::now()->subDays(5);

        $loanRequests = LoanApplication::where('status', '!=', 'APPROVED')->where('status', '!=', 'CANCELED')
            ->where('created_at', '<=', $thresholdDate)
            ->get();

        foreach ($loanRequests as $loanRequest) {
            $loanRequest->update(['status' => 'CANCELED']);
            $this->info("Loan request ID {$loanRequest->identifier} has been cancelled.");


            $subject = 'Your Loan Request Has Been Canceled';
            $message = "We regret to inform you that your loan request (Application ID: $loanRequest->identifier) has been automatically canceled due to no approval within the required 5-day period.";
            $mailable = (new MailMessage)
                ->greeting('Hello '.$loanRequest->customer->name)
                ->subject($subject)
                ->line($message)
                ->line("Application ID: ".$loanRequest->identifier)
                ->line("Requested Amount: ".$loanRequest->requested_amount)
                ->line("Submission Date: ".Carbon::parse($loanRequest->created_at)->format('F jS, Y'))
                ->line("If you still need financing, you may consider reapplying.")
                ->line("")
                ->line('Best Regards,')
                ->line('The 10MG Health Team');

            Notification::route('mail', [
                $loanRequest->customer->email => $loanRequest->customer->name,
            ])->notify(new LoanSubmissionNotification($mailable));


        }
    }
}
