<?php

namespace App\Http\Requests;

use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateInviteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        return $user && ($user->ownerBusinessType || $user->businesses()->firstWhere('user_id', $this->id)) && ($user->hasRole('vendor') || $user->hasRole('admin'));
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $roleName = $this->user()->roles()->first()->name;

        if($this->input('role') == 'admin'){
            $this->merge([
                'role' => $roleName,
            ]);
        }
        $role = Role::where('name', $this->input('role'))
            ->whereIn('name', ['admin', 'vendor', 'support', 'operation'])
            ->first();

        $this->merge([
            'full_name' => $this->input('fullName'),
            'role_id' => $role ? $role->id : null, // Merge the role ID or null if not found
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $user = $this->user();

        return [
            'full_name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('invites')->where(fn ($query) => $query->where('business_id', $user->ownerBusinessType?->id ?: $user->businesses()->firstWhere('user_id', $this->id)?->id)->whereNotIn('status', ['REJECTED', 'REMOVED'])),
            ],
            'role_id' => ['required', 'exists:roles,id'],
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
            'role_id.required' => $this->input('role') ? 'The selected role does not exist.' : 'The role field is required.',
            'role_id.exists' => 'The selected role does not exist.',
        ];
    }
}
