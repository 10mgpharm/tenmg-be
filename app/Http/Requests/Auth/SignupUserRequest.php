<?php

namespace App\Http\Requests\Auth;

use App\Enums\BusinessType;
use App\Models\User;
use Illuminate\Validation\Rules;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;

class SignupUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
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
            'businessType' => [
                'required',
                'string',
                'in:' . implode(',', array_map(fn($type) => $type->tolowercase(), BusinessType::allowedForRegistration()))
            ],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Rules\Password::default()],
            'termsAndConditions' => 'required|accepted',
        ];
    }

    public function messages(): array
    {
        return [
            'termsAndConditions.required' => 'You must agree to the terms and conditions.',
            'businessType.in' => 'The business type must be either supplier or pharmacy.',
        ];
    }

    /**
     * Attempt to register the user.
     *
     * @throws \Illuminate\Validation\ValidationException
     * @return User | null
     */
    public function register(): User
    {
        $validated = $this->validated();
        $businessType = BusinessType::from(strtoupper($validated['businessType']))->toLowercase();

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'business_type' => $businessType,
        ]);

        $user->assignRole($businessType);

        return $user;
    }
}
