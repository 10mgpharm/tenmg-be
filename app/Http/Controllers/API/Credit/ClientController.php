<?php

namespace App\Http\Controllers\API\Credit;

use App\Http\Controllers\Controller;
use App\Http\Requests\Vendor\StartApplicationRequest;
use App\Services\Interfaces\IClientService;
use App\Services\LoanApplicationService;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function __construct(
        public IClientService $clientService,
        public LoanApplicationService $loanApplicationService
    ) {}

    public function getCustomers(Request $request)
    {
        $business = $request->business;

        $apiKeys = $this->clientService->getDemoCustomers($business);

        return $this->returnJsonResponse(
            message: 'Customers fetched successfully',
            data: $apiKeys
        );
    }

    public function startApplication(StartApplicationRequest $request)
    {
        $request->validated();

        $business = $request->business;

        $applicationLink = $this->loanApplicationService->generateExternalApplicationLink(
            vendor: $business,
            data: $request->toArray(),
        );

        return $this->returnJsonResponse(
            message: 'Application initialized successfully',
            data: [
                'url' => $applicationLink,
            ]
        );
    }
}
