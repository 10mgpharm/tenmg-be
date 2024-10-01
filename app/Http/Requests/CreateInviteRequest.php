<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VendorBusinessSettingAddTeamMemberRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        return $user && $user->ownerBusinessType && $user->hasRole('vendor');
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'full_name' => $this->input('fullName'),
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
            'full_name' => ['required', 'string', 'max:255',],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('invites')->where(fn($query) => $query->where('business_id', $this->business_id)->whereNotIn('status', ['REJECTED', 'REMOVED'])),
            ],
            'role' => ['required', 'in:admin,support,operation'],
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
            'email.unique' => 'This email has already been invited to this business.',
        ];
    }
}
