<?php

namespace App\Services;

use App\Helpers\UtilityHelper;
use App\Models\Business;
use App\Models\Customer;
use App\Models\DebitMandate;
use App\Models\LoanApplication;
use App\Models\User;
use App\Notifications\CustomerLoanApplicationNotification;
use App\Repositories\LoanApplicationRepository;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Notification;

class LoanApplicationService
{
    public function __construct(
        private LoanApplicationRepository $loanApplicationRepository,
        private AuthService $authService,
        private NotificationService $notificationService,
    ) {}

    // Create Loan Application
    public function createApplication(array $data)
    {

        $mandate = DebitMandate::where('customer_id', $data['customerId'])->first();

        if (! $mandate) {
            throw new Exception('Customer does not have a debit mandate', Response::HTTP_BAD_REQUEST);
        }

        $business = $this->authService->getBusiness();

        if ($business) {
            $data['businessId'] = $business->id;
        }

        $interestData = UtilityHelper::calculateInterestAmount($data['requestedAmount'], $data['durationInMonths']);
        $data['interestRate'] = $interestData['interestRate'];
        $data['interestAmount'] = $interestData['interestAmount'];
        $data['totalAmount'] = $interestData['totalAmount'];

        $application = $this->loanApplicationRepository->create($data);

        // TODO: notification to customer here

        return $application;
    }

    public function updateApplication(array $data): LoanApplication
    {
        $application = $this->loanApplicationRepository->findByReference($data['reference']);

        if ($application->status != 'PENDING') {
            throw new Exception('Application cannot be updated', Response::HTTP_BAD_REQUEST);
        }

        $interestData = UtilityHelper::calculateInterestAmount($data['requestedAmount'], $data['durationInMonths']);
        $data['interestRate'] = $interestData['interestRate'];
        $data['interestAmount'] = $interestData['interestAmount'];
        $data['totalAmount'] = $interestData['totalAmount'];

        return $this->loanApplicationRepository->update(id: $application->id, data: $data);
    }

    // Submit Loan Application link
    public function sendApplicationLink(array $data)
    {

        $customer = Customer::find($data['customerId']);
        if ($customer->credit_score_id == null) {
            throw new Exception('Customer not evaluated', Response::HTTP_BAD_REQUEST);
        }

        $vendor = $this->authService->getBusiness();
        $user = User::find($vendor->owner_id);

        $data['businessId'] = $vendor->id;
        $data['source'] = 'API';

        $application = $this->loanApplicationRepository->create($data); //

        $token = $user->createToken('Full Access Token', ['full']);

        $link = config('app.frontend_url').'/widgets/applications/'.$application->identifier.'?token='.$token->accessToken;

        $customer = $application->customer;

        // notifation to customer here
        Notification::route('mail', [
            $customer?->email => $customer?->name,
        ])->notify(new CustomerLoanApplicationNotification($link));

        return $link;
    }

    // Submit Loan Application link
    public function generateExternalApplicationLink(Business $vendor, array $data)
    {
        if (array_key_exists('reference', $data) && isset($data['reference'])) {
            $customer = Customer::firstOrCreate(
                [
                    'reference' => $data['reference'],
                    'business_id' => $vendor->id,
                ],
                [
                    'business_id' => $vendor->id,
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'phone' => $data['phone'],
                    'reference' => array_key_exists('reference', $data) ? $data['reference'] : null,
                    'identifier' => UtilityHelper::generateSlug('CUS'),
                    'active' => true,
                ]);

        } else {
            $customer = Customer::create(
                [
                    'business_id' => $vendor->id,
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'phone' => $data['phone'],
                    'reference' => array_key_exists('reference', $data) ? $data['reference'] : null,
                    'identifier' => UtilityHelper::generateSlug('CUS'),
                    'active' => true,
                ]);
        }

        $user = User::find($vendor->owner_id);

        $data['businessId'] = $vendor->id;
        $data['customerId'] = $customer->id;
        $data['requestedAmount'] = $customer->requestedAmount;
        $data['source'] = 'API';

        $application = $this->loanApplicationRepository->create($data);

        $token = $user->createToken('Full Access Token', ['full']);

        $link = config('app.frontend_url').'/widgets/applications/'.$application->identifier.'?token='.$token->accessToken;

        $customer = $application->customer;

        // notifation to customer here
        Notification::route('mail', [
            $customer?->email => $customer?->name,
        ])->notify(new CustomerLoanApplicationNotification($link));

        return $link;
    }

    public function verifyApplicationLink($reference)
    {
        return $this->loanApplicationRepository->verifyApplicationLink($reference);
    }

    // Retrieve Customizations
    public function getVendorCustomisations()
    {
        // Retrieve vendor-specific customization like name, logo, etc.
        return [
            'name' => 'Vendor Name',
            'logo' => 'https://example.com/logo.png',
            'color' => '#123456',
        ];
    }

    // Retrive Loan Application
    public function getLoanApplicationByReference(string $reference)
    {
        return $this->loanApplicationRepository->findByReference($reference);
    }

    // Retrive All Loan Applications
    public function getLoanApplications(array $filter, int $perPage): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $business = $this->authService->getBusiness();
        if ($business->type == 'ADMIN') {
            return $this->loanApplicationRepository->getAll($filter, $perPage);
        }

        return $this->getLoanApplicationsByFilter($filter, $perPage);
    }

    public function getLoanApplicationsByFilter(array $filter, int $perPage): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $business = $this->authService->getBusiness();
        if ($business->type != 'ADMIN') {
            $filter['businessId'] = $business->id;
        }

        $applications = $this->loanApplicationRepository->filter($filter, $perPage);

        return $applications;
    }

    // Review Loan Application (approve/reject)
    public function reviewApplication(int $applicationId, string $status, $offerAmount = null)
    {
        $application = $this->loanApplicationRepository->findById($applicationId);

        $subject = '';
        $message = '';

        if ($status == 'approve') {
            $application = $this->approveApplication($application->id);
            $subject = 'Loan Application Approved';
            $message = "Your loan application with reference {$application->identifier} has been approved. You will receive a loan offer shortly.";
        } else {
            $application = $this->closeApplication($applicationId);
            $subject = 'Loan Application Rejected';
            $message = "Your loan application with reference {$application->identifier} has been rejected. If you have any questions, feel free to contact us.";
        }

        // Send notification to customer here
        $this->notificationService->sendCustomerNotification($application->customer_id, $subject, $message);

        return $application;
    }

    // Other methods for listing, filtering, deleting, etc.
    public function getApplicationDetails(int $id): ?LoanApplication
    {
        return $this->loanApplicationRepository->findById($id);
    }

    public function closeApplication(int $id): LoanApplication
    {
        return $this->loanApplicationRepository->review($id, 'CLOSED');
    }

    public function approveApplication(int $id): LoanApplication
    {
        return $this->loanApplicationRepository->review($id, 'APPROVED');
    }

    public function deleteApplication(int $id): bool
    {
        return $this->loanApplicationRepository->deleteById($id);
    }

    public function getApplicationsByCustomer(int $customerId): Collection
    {
        return $this->loanApplicationRepository->getApplicationsByCustomer($customerId);
    }
}
