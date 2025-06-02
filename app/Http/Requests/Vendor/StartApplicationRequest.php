<?php

namespace App\Http\Requests\Vendor;

use Illuminate\Foundation\Http\FormRequest;

class StartApplicationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.*.
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
            'requestedAmount' => ['required', 'min:0', 'numeric'],
            'txnReference' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
