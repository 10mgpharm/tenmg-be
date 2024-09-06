<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListCustomersRequest extends FormRequest
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
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|max:255',
            'vendorId' => 'sometimes|integer|exists:businesses,id',
            'createdAtStart' => 'sometimes|date|before_or_equal:createdAtEnd',
            'createdAtEnd' => 'sometimes|date|after_or_equal:createdAtStart',
            'page' => 'sometimes|integer|min:1',
            'perPage' => 'sometimes|integer|min:1|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'name.string' => 'The name must be a string.',
            'email.email' => 'Please provide a valid email address.',
            'vendorId.integer' => 'The vendor ID must be an integer.',
            'vendorId.exists' => 'The selected vendor does not exist.',
            'createdAtStart.date' => 'The start date must be a valid date.',
            'createdAtStart.before_or_equal' => 'The start date must be before or equal to the end date.',
            'createdAtEnd.date' => 'The end date must be a valid date.',
            'createdAtEnd.after_or_equal' => 'The end date must be after or equal to the start date.',
            'page.integer' => 'The page number must be an integer.',
            'page.min' => 'The page number must be at least 1.',
            'perPage.integer' => 'The per page value must be an integer.',
            'perPage.min' => 'The per page value must be at least 1.',
            'perPage.max' => 'The per page value may not be greater than 100.',
        ];
    }
}
