<?php

namespace App\Http\Requests\Vendor;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GenerateApiKeyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        if (! $user) {
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
            'type' => ['required', 'string', Rule::in('public', 'secret')],
            'environment' => ['required', 'string', Rule::in('test', 'live')],
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
