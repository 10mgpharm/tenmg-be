<?php

namespace App\Services\Interfaces;

use App\Models\CreditOffer;
use App\Models\LoanApplication;

interface INotificationService
{
    public function sendLoanApplicationNotification(LoanApplication $loanApplication);

    public function sendLoanOfferNotification(CreditOffer $creditOffer);

    public function sendOfferAcceptanceNotification(CreditOffer $creditOffer);

    public function sendOfferRejectionNotification(CreditOffer $creditOffer);

    public function sendAdminNotification(string $subject, string $message);

    public function sendCustomerNotification(int $customerId, string $subject, string $message);

    public function sendEmail(array $data);
}
