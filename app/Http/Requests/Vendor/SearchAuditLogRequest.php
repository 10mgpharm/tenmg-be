<?php

namespace App\Http\Requests\Vendor;

use Illuminate\Foundation\Http\FormRequest;

class SearchAuditLogRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        return $user && $user->hasRole('vendor');
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'crud_type' => $this->input('crudType'),
            'from_date' => $this->input('fromDate'),
            'to_date' => $this->input('toDate'),
            'ip' => $this->input('ip'),
            'ip_address' => $this->input('ipAddress'),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'crud_type' => ['nullable', 'string'],
            'event' => ['nullable', 'string'],
            'ip' => ['nullable', 'ip'],
            'ip_address' => ['nullable', 'ip'],
            'from_date' => ['nullable', 'date', 'before_or_equal:today'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date', 'before_or_equal:today'],
        ];
    }

    /**
     * Custom response for failed authorization.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function failedAuthorization()
    {
        abort(response()->json([
            'message' => 'You are not authorized to search these resources.',
        ], 403));
    }
}
