<?php

namespace App\Http\Requests;

use App\Rules\VerifyTwoFactorCode;
use Illuminate\Foundation\Http\FormRequest;

class VerifyTwoFactorRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        // First, check if the user is authenticated
        if (! $user) {
            return false;
        }

        // Then, check if the user has 2FA setup and is turned on.
        if (! $user->two_factor_secret || ! $user->use_two_factor) {
            return false;
        }

        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'code' => [
                'string',
                'required',
                'size:6',
                new VerifyTwoFactorCode,
            ]
        ];
    }

    /**
     * Custom response for failed authorization.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function failedAuthorization()
    {
        $user = $this->user();
        // Check if the user is authenticated
        if (! $user) {
            abort(response()->json([
                'message' => 'Unauthenticated.'
            ], 401));
        }

        // Check if the user has 2FA already set up
        if ($user->two_factor_secret) {
            abort(response()->json([
                'message' => 'Two-factor authentication is not enabled for this account.'
            ], 403));
        }
    }
}
