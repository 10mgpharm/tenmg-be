<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
            'role_id' => $this->input('roleId'),
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
            'email' => ['required', 'string', 'email', 'max:255', 'unique:team_members,email'],
            // 'role_id' => ['required', 'exists:roles,id']
        ];
    }
}
