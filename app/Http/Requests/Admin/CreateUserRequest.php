<?php

namespace App\Http\Requests\Admin;

use App\Helpers\UtilityHelper;
use App\Models\Role;
use App\Rules\BusinessEmail;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        // check if user is authenticated.
        if(!$user){
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
        $roleName = $this->input('businessType') == 'pharmacy' ? 'customer' : $this->input('businessType');
        $role = Role::where('name', $roleName)
        ->whereIn('name', UtilityHelper::getAllowedBusinessTypes())
        ->first();

        $this->merge([
            'business_type' => $this->input('businessType'),
            'email' => $this->input('businessEmail'), 
            'business_name' => $this->input('businessName'),
            'role_id' => $role ? $role->id : null, 
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
            'business_type' => ['required', 'string', 'in:vendor,supplier,pharmacy'],
            'email' => [
                'required', 'string', 'lowercase', 'email',
                'max:255', 'unique:users,email',
                new BusinessEmail,
            ],
            'business_name' => ['required', 'string', 'max:255', 'unique:businesses,name'],
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
            'email.unique' => 'This business email is already associated to a business.',
            'email.required' => 'The business email field is required.',
            'email.max' => 'The business email field must not be greater than 255 characters.',
            'email.string' => 'The business email field must be a string.',
            'email.lowercase' => 'The business email field must be in lowercase.',
            'email.email' => 'The business email field must be a valid email address.',

            'business_name.required' => 'The business name field is required.',
            'business_name.max' => 'The business name field must not be greater than 255 characters.',
            'business_name.string' => 'The business name field must be a string.',

            'role_id.required' => $this->input('businessType') ? 'The selected role does not exist.' : 'The role field is required.',
            'role_id.exists' => 'The selected role does not exist.',
        ];
    }
}
