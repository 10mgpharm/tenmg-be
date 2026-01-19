<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Currency\ListCurrenciesRequest;
use App\Http\Requests\Currency\ShowCurrencyRequest;
use App\Http\Requests\Currency\UpdateCurrencyRequest;
use App\Http\Resources\Currency\CurrencyResource;
use App\Models\Currency;
use Illuminate\Http\JsonResponse;

class CurrencyController extends Controller
{
    /**
     * List currencies (admin only via route middleware).
     */
    public function index(ListCurrenciesRequest $request): JsonResponse
    {

        $query = Currency::with([
            'virtualAccountProvider',
            'tempVirtualAccountProvider',
            'virtualCardProvider',
            'bankTransferCollectionProvider',
            'mobileMoneyCollectionProvider',
            'bankTransferPayoutProvider',
            'mobileMoneyPayoutProvider',
        ])
            ->when($request->input('status'), function ($query, $status) {
                $query->where('status', $status);
            })
            ->when($request->input('classification'), function ($query, $classification) {
                $query->where('classification', $classification);
            })
            ->when($request->input('code'), function ($query, $code) {
                $query->where('code', $code);
            });

        $perPage = $request->input('per_page', 15);

        $currencies = $query->paginate($perPage)->withQueryString();

        return $this->returnJsonResponse(
            message: 'Currencies successfully fetched.',
            data: CurrencyResource::collection($currencies)->response()->getData(true)
        );
    }

    /**
     * Show a single currency (admin only via route middleware).
     */
    public function show(ShowCurrencyRequest $request, Currency $currency): JsonResponse
    {
        $currency->load([
            'virtualAccountProvider',
            'tempVirtualAccountProvider',
            'virtualCardProvider',
            'bankTransferCollectionProvider',
            'mobileMoneyCollectionProvider',
            'bankTransferPayoutProvider',
            'mobileMoneyPayoutProvider',
        ]);

        return $this->returnJsonResponse(
            message: 'Currency successfully fetched.',
            data: new CurrencyResource($currency)
        );
    }

    /**
     * Update a currency (admin only via route middleware).
     */
    public function update(UpdateCurrencyRequest $request, Currency $currency): JsonResponse
    {
        // Only fill the fields that are present in the request
        $currency->fill($request->only(array_keys($request->validated())));
        $currency->save();

        // Reload with relationships
        $currency->load([
            'virtualAccountProvider',
            'tempVirtualAccountProvider',
            'virtualCardProvider',
            'bankTransferCollectionProvider',
            'mobileMoneyCollectionProvider',
            'bankTransferPayoutProvider',
            'mobileMoneyPayoutProvider',
        ]);

        return $this->returnJsonResponse(
            message: 'Currency successfully updated.',
            data: new CurrencyResource($currency)
        );
    }
}
