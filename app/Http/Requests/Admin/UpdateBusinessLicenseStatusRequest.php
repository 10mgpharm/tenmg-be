<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBusinessLicenseStatusRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        // check if user is authenticated.
        if (!$user) {
            return false;
        }

        // check if user is an admin.
        return $user->hasRole('admin');
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'license_verification_status' => $this->input('status'),
            'license_verification_comment' => $this->input('comment'),
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
            'license_verification_status' => ['required', 'string', 'in:APPROVED,REJECTED,PENDING'],
            'license_verification_comment' => ['required', 'string', 'min:3'],
        ];
    }


    /**
     * Custom error messages for validation failures.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'license_verification_status.required' => 'The status field is required.',
            'license_verification_status.string' => 'The status field must be a string.',
            'license_verification_status.in' => 'The selected status is invalid.',

            'license_verification_comment.required' => 'The comment field is required.',
            'license_verification_comment.string' => 'The comment field must be a string.',
            'license_verification_comment.min' => 'The comment field must be at least 3 characters.',
        ];
    }
}
