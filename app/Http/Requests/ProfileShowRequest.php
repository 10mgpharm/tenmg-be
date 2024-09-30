<?php

namespace App\Http\Requests;

use App\Enums\BusinessType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class ProfileShowRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return !!$this->user();
    }

    /**
     * Prepare the data for validation.
     *
     * This method merges route parameters into the request data, so that they
     * can be validated as part of the request body.
     */
    protected function prepareForValidation()
    {
        $businessType = strtoupper(last(explode('/', $this->route()->getPrefix())));
        
        $this->merge([
            'businessType' => $businessType,
            'id' => $this->route('id'),
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
            'businessType' => [
                'required',
                'string',
                new Enum(BusinessType::class),
                "exists:businesses,type,owner_id,{$this->id}",
            ],
            'id' => [
                'required',
                'integer',
                "exists:users,id,id,{$this->user()->id}",
            ],
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
            'businessType.exists' => 'The selected business type does not match the provided user ID.',
            'id.exists' => 'The provided ID does not match the authenticated user.',
        ];
    }
}
