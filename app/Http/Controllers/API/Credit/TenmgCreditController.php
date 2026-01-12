<?php

namespace App\Http\Controllers\API\Credit;

use App\Http\Controllers\Controller;
use App\Http\Requests\Credit\TenmgCreditInitiateRequest;
use App\Models\Business;
use App\Models\TenmgCreditRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TenmgCreditController extends Controller
{
    /**
     * Initiate Tenmg credit flow.
     * Accepts the same payload currently sent by Marq, persists it, and returns an SDK URL.
     */
    public function initiate(TenmgCreditInitiateRequest $request): JsonResponse
    {
        $payload = $request->validated() ?: $request->all();

        // Remove business-related fields from payload
        $businessFields = ['businessname', 'business', 'business_id', 'business_name'];
        $filteredPayload = array_diff_key($payload, array_flip($businessFields));

        $requestId = 'TENMGREQ-'.Str::upper(Str::random(12));
        $sdkBaseUrl = rtrim(config('services.tenmg_credit.sdk_base_url') ?? config('app.url'), '/');
        $sdkUrl = $sdkBaseUrl.'/tenmg-credit?request_id='.$requestId;

        $business = $request->input('business');
        $initiatedBy = ($business instanceof Business) ? (string) $business->id : null;

        $record = TenmgCreditRequest::create([
            'request_id' => $requestId,
            'payload' => $filteredPayload,
            'status' => 'pending',
            'sdk_url' => $sdkUrl,
            'initiated_by' => $initiatedBy,
        ]);

        Log::info('Tenmg credit request initiated', [
            'request_id' => $requestId,
            'initiated_by' => $initiatedBy,
            'payload' => $filteredPayload,
        ]);

        return $this->returnJsonResponse(
            message: 'Tenmg credit request initiated',
            data: [
                'request_id' => $record->request_id,
                'sdk_url' => $record->sdk_url,
                'status' => $record->status,
            ]
        );
    }

    /**
     * Retrieve a stored Tenmg credit payload by request ID.
     */
    public function show(string $requestId): JsonResponse
    {
        $record = TenmgCreditRequest::where('request_id', $requestId)->first();

        if (! $record) {
            return $this->returnJsonResponse(
                message: 'Tenmg credit request not found',
                data: null,
                status: 'failed',
                statusCode: Response::HTTP_NOT_FOUND
            );
        }

        return $this->returnJsonResponse(
            message: 'Tenmg credit request retrieved',
            data: [
                'request_id' => $record->request_id,
                'payload' => $record->payload,
                'status' => $record->status,
                'sdk_url' => $record->sdk_url,
                'created_at' => $record->created_at,
            ]
        );
    }
}
