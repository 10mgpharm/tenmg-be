<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SetupTwoFactorRequest extends FormRequest
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

        // Then, check if the user has 2FA already enabled
        if ($user->two_factor_secret) {
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
        return [];
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
                'message' => 'User is not authenticated.'
            ], 401));
        }

        // Check if the user has 2FA already set up
        if ($user->two_factor_secret) {
            abort(response()->json([
                'message' => 'Two-factor authentication is already setup for this account.'
            ], 403));
        }
    }
}
