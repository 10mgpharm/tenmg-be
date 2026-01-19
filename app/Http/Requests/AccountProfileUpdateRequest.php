<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AccountProfileUpdateRequest extends FormRequest
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
            'name' => [
                $this->isMethod('patch') ? 'required' : 'sometimes',
                'string',
                'min:3',
                'max:255',
            ],
            'email' => [
                $this->isMethod('patch') ? 'required' : 'sometimes',
                'string',
                'min:3',
                'max:255',
                Rule::unique('users', 'email')->ignore($this->user()->id),
            ],
            'phone' => [
                'sometimes',
                'nullable',
                'string',
                // Accept 0XXXXXXXXXX or +234XXXXXXXXXX or 234XXXXXXXXXX (we normalize before saving)
                'regex:/^(0\d{10}|\+?234\d{10})$/',
                Rule::unique('users', 'phone')->ignore($this->user()->id),
            ],
            'profilePicture' => [
                $this->isMethod('post') ? 'required' : 'sometimes',
                'image',
                'max:10240',
            ],
        ];

    }
}
