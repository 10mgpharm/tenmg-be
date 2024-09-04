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
            'vendor_id' => 'sometimes|integer|exists:businesses,id',
            'created_at_start' => 'sometimes|date|before_or_equal:created_at_end',
            'created_at_end' => 'sometimes|date|after_or_equal:created_at_start',
            'page' => 'sometimes|integer|min:1',
            'per_page' => 'sometimes|integer|min:1|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'name.string' => 'The name must be a string.',
            'email.email' => 'Please provide a valid email address.',
            'vendor_id.integer' => 'The vendor ID must be an integer.',
            'vendor_id.exists' => 'The selected vendor does not exist.',
            'created_at_start.date' => 'The start date must be a valid date.',
            'created_at_start.before_or_equal' => 'The start date must be before or equal to the end date.',
            'created_at_end.date' => 'The end date must be a valid date.',
            'created_at_end.after_or_equal' => 'The end date must be after or equal to the start date.',
            'page.integer' => 'The page number must be an integer.',
            'page.min' => 'The page number must be at least 1.',
            'per_page.integer' => 'The per page value must be an integer.',
            'per_page.min' => 'The per page value must be at least 1.',
            'per_page.max' => 'The per page value may not be greater than 100.',
        ];
    }
}
