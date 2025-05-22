<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use App\Enums\OtpType;

class ResendOtpRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // You can modify this to include authorization logic.
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'type' => ['required', 'string', new Enum(OtpType::class)],
            'email' => [
                'string',
                'email',
                $this->is('*/auth/*') ? 'required' : 'nullable',
        ],
        ];
    }

}
