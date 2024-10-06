<?php

namespace App\Services;

use App\Models\CreditOffer;
use App\Models\Customer;
use App\Models\LoanApplication;
use App\Models\RepaymentSchedule;
use App\Models\User;
use App\Services\Interfaces\INotificationService;
use Illuminate\Support\Facades\Mail;

class NotificationService implements INotificationService
{
    // 1. Notify customer when a new loan application is submitted
    public function sendLoanApplicationNotification(LoanApplication $loanApplication)
    {
        $subject = 'Loan Application Submitted';
        $message = "Your loan application with reference {$loanApplication->identifier} has been submitted successfully.";

        $this->sendCustomerNotification($loanApplication->customer_id, $subject, $message);
    }

    // 2. Notify customer when a loan offer is sent
    public function sendLoanOfferNotification(CreditOffer $creditOffer)
    {
        $subject = 'Loan Offer Available';
        $message = "You have received a new loan offer with reference {$creditOffer->identifier}. Click the link to review the offer.";

        $this->sendCustomerNotification($creditOffer->customer_id, $subject, $message);
    }

    // 3. Notify customer when they accept a loan offer
    public function sendOfferAcceptanceNotification(CreditOffer $creditOffer)
    {
        $subject = 'Loan Offer Accepted';
        $message = "You have successfully accepted the loan offer with reference {$creditOffer->identifier}. Your loan process will now proceed.";

        $this->sendCustomerNotification($creditOffer->customer_id, $subject, $message);
    }

    // 4. Notify customer when they reject a loan offer
    public function sendOfferRejectionNotification(CreditOffer $creditOffer)
    {
        $subject = 'Loan Offer Rejected';
        $message = "You have rejected the loan offer with reference {$creditOffer->identifier}. If you have any questions, feel free to contact us.";

        $this->sendCustomerNotification($creditOffer->customer_id, $subject, $message);
    }

    // 5. Notify admin about a loan application or offer
    public function sendAdminNotification(string $subject, string $message)
    {
        // Fetch admin users (in this case, 10mg admins)
        $adminEmails = User::where('role', 'ADMIN')->pluck('email')->toArray();

        foreach ($adminEmails as $email) {
            $this->sendEmail([
                'to' => $email,
                'subject' => $subject,
                'message' => $message,
            ]);
        }
    }

    // 6. Notify specific customer
    public function sendCustomerNotification(int $customerId, string $subject, string $message)
    {
        $customer = Customer::findOrFail($customerId);
        $email = $customer->email;

        $this->sendEmail([
            'to' => $email,
            'subject' => $subject,
            'message' => $message,
        ]);
    }

    // 7. Helper function to send an email
    public function sendEmail(array $data)
    {
        Mail::raw($data['message'], function ($message) use ($data) {
            $message->to($data['to'])
                ->subject($data['subject']);
        });
    }

    /**
     * Send repayment reminder to customer.
     */
    public function sendRepaymentReminder(RepaymentSchedule $repayment)
    {
        // Get customer and loan information
        $customer = $repayment->loan->customer;

        $link = config('app.frontend_url') . '/widgets/repayment/shedule/' . $repayment->id;


        $subject = 'Loan Repayment Reminder';
        $message = "Hi {$repayment?->loan?->customer?->name}, Kindly use the below link to make your upcoming repayment. \n\nClick {$link} to make payment.";


        $this->sendCustomerNotification($customer->id, $subject, $message);

        // Mail::to($customer->email)->send(new RepaymentReminderMail($repayment));
    }

    public function sendLoanLiquidationNotification($customer, $loan)
    {
        $subject = 'Loan Liquidation';
        $message = "Dear {$customer->name}, your loan (ID: {$loan->identifier}) has been successfully paid off.";

        $this->sendCustomerNotification($customer->id, $subject, $message);

        // Send email or SMS
        // Mail::to($customer->email)->send(new LoanLiquidationMail($loan));
        // Admin notification logic here
    }
}
