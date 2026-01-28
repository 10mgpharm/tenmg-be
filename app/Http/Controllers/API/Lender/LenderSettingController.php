<?php

namespace App\Http\Controllers\API\Lender;

use App\Http\Controllers\Controller;
use App\Models\LenderSetting;
use App\Settings\LoanSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LenderSettingController extends Controller
{
    /**
     * Get the authenticated lender's settings, including effective rate.
     */
    public function show(Request $request): JsonResponse
    {
        $business = $request->user()->ownerBusinessType
            ?: $request->user()->businesses()->firstWhere('user_id', $request->user()->id);

        $setting = LenderSetting::firstOrCreate(
            ['business_id' => $business->id],
            [
                'rate' => 0,
                'instruction' => null,
                'instruction_config' => [
                    'active' => true,
                    'supported_tenors' => [1, 2, 3, 4],
                    'min_amount' => 20000,
                    'max_amount' => null,
                ],
            ]
        );

        $loanSettings = new LoanSettings;
        $lenderRate = (float) ($setting->rate ?? 0);

        // Enforce lender rate within 0–9% for safety, but do not expose platform margin or effective total in the response
        $lenderRateClamped = max(0.0, min($lenderRate, 9.0));

        return $this->returnJsonResponse(
            data: [
                'rate' => $lenderRateClamped,
                'instruction' => $setting->instruction,
                'instruction_config' => $setting->instruction_config,
            ],
            message: 'Lender settings retrieved successfully'
        );
    }

    /**
     * Update the authenticated lender's settings.
     */
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'rate' => 'required|numeric|min:0|max:9',
            'instruction' => 'nullable|string',
            'instruction_config' => 'nullable|array',
            'instruction_config.active' => 'sometimes|boolean',
            'instruction_config.supported_tenors' => 'sometimes|array',
            'instruction_config.supported_tenors.*' => 'in:1,2,3,4',
            'instruction_config.min_amount' => 'sometimes|numeric|min:20000',
            'instruction_config.max_amount' => 'nullable|numeric|gt:instruction_config.min_amount',
            'instruction_config.disbursement_channel' => 'sometimes|string',
            'instruction_config.require_manual_approval' => 'sometimes|boolean',
            'instruction_config.verification_required' => 'sometimes|boolean',
        ]);

        $user = $request->user();
        $business = $user->ownerBusinessType
            ?: $user->businesses()->firstWhere('user_id', $user->id);

        $setting = LenderSetting::firstOrCreate(
            ['business_id' => $business->id]
        );

        $config = $setting->instruction_config ?? [];

        if (isset($validated['instruction_config'])) {
            $config = array_merge($config, $validated['instruction_config']);
        }

        // Ensure supported_tenors default to [1,2,3,4] if missing or empty
        if (empty($config['supported_tenors']) || ! is_array($config['supported_tenors'])) {
            $config['supported_tenors'] = [1, 2, 3, 4];
        }

        // Enforce minimum amount default of 20,000
        if (! isset($config['min_amount']) || (float) $config['min_amount'] < 20000) {
            $config['min_amount'] = 20000;
        }

        $setting->rate = $validated['rate'];
        $setting->instruction = $validated['instruction'] ?? $setting->instruction;
        $setting->instruction_config = $config;
        $setting->save();

        return $this->show($request);
    }
}
