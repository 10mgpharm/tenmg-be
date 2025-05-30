<?php

namespace App\Http\Requests\Vendor;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class UpdateApiKeyUrlRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        if (!$user) {
            return false;
        }

        return $user->ownerBusinessType && $user->hasRole('vendor');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'environment' => ['required', 'string', Rule::in('test', 'live')],
            'webhookUrl' => ['sometimes', 'nullable', 'string', 'url', function ($attribute, $value, $fail) {
                if (! preg_match('/^https?:\/\//', $value)) {
                    $fail($attribute.' must be a valid URL starting with http:// or https://');
                }
            }],
            'callbackUrl' => ['sometimes', 'nullable', 'string', 'url', function ($attribute, $value, $fail) {
                if (! preg_match('/^https?:\/\//', $value)) {
                    $fail($attribute.' must be a valid URL starting with http:// or https://');
                }
            }],
            'transactionUrl' => ['sometimes', 'nullable', 'string', 'url', function ($attribute, $value, $fail) {
                if (! preg_match('/^https?:\/\//', $value)) {
                    $fail($attribute.' must be a valid URL starting with http:// or https://');
                }
            }],
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

        if (! $user->hasRole('vendor')) {
            abort(response()->json([
                'message' => 'You do not have the required permission to perform this operation',
            ], 403));
        }

        if (! $user->ownerBusinessType) {
            abort(response()->json([
                'message' => 'You must be the business owner to generate new key',
            ], 403));
        }
    }
}
