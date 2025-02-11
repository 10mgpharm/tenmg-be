<?php

namespace App\Http\Controllers\API\Storefront;

use App\Http\Controllers\Controller;
use App\Repositories\FincraPaymentRepository;
use App\Services\Storefront\EcommerceOrderService;
use Illuminate\Http\Request;

class FincraWebhookController extends Controller
{

    protected $fincraPaymentRepository;

    function __construct(FincraPaymentRepository $fincraPaymentRepository)
    {
        $this->fincraPaymentRepository = $fincraPaymentRepository;
    }

    function verifyFincraPaymentWebHook(Request $request)
    {
        return $this->fincraPaymentRepository->verifyFincraPaymentWebHook($request);
    }
}
