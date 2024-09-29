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
            'name' => ['string', 'nullable', 'sometimes', 'min:3', 'max:255'],
            'email' => [
                'required',
                'string',
                'min:3',
                'max:255',
                Rule::unique('users', 'email')
                    ->ignore($this->user()->id),
            ],
            'profilePicture' => [
                'sometimes',
                'nullable',
                'image',
                'max:10240'
            ],
        ];
    }
}
