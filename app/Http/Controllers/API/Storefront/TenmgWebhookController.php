<?php

namespace App\Http\Controllers\API\Storefront;

use App\Http\Controllers\Controller;
use App\Repositories\FincraPaymentRepository;
use App\Services\Storefront\EcommerceOrderService;
use Illuminate\Http\Request;
use TenmgPaymentRepository;

class TenmgWebhookController extends Controller
{

    protected $tenmgPaymentRepository;

    function __construct(TenmgPaymentRepository $tenmgPaymentRepository)
    {
        $this->tenmgPaymentRepository = $tenmgPaymentRepository;
    }

    function verifyTenmgCreditPaymentWebHook(Request $request)
    {
        return $this->tenmgPaymentRepository->verifyTenmgCreditPaymentWebHook($request);
    }
}
