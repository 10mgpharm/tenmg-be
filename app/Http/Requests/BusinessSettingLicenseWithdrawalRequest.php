<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BusinessSettingLicenseWithdrawalRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        return $user && $user->ownerBusinessType;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [];
    }

    /**
     * Custom response for failed authorization.
     *
     * @return void
     */
    protected function failedAuthorization()
    {
        if (! $this->user()) {
            abort(response()->json([
                'message' => 'Unauthenticated.',
            ], 401));
        }

        abort(response()->json([
            'message' => 'You do not have access to view this business resource. Only the business owner is permitted.',
        ], 403));
    }
}
