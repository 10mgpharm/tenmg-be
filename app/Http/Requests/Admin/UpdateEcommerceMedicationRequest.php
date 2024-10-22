<?php

namespace App\Http\Requests\Admin;

use App\Enums\StatusEnum;
use App\Models\EcommerceMedicationType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class UpdateEcommerceMedicationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        return $user && $user->hasRole('admin');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // Retrieve the current medication type from the route
        $medication_type = $this->route('medication_type');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique(EcommerceMedicationType::class)->ignore($medication_type->id)
            ],
            'status' => [
                'sometimes',
                'string',
                new Enum(StatusEnum::class),
                function ($attribute, $value, $fail) {
                    if ($this->active && !in_array($value, [StatusEnum::APPROVED->value, null])) {
                        $fail('The status must be "APPROVED" or null when active is true.');
                    }
                },
            ],
            'active' => [
                'sometimes',
                'boolean',
            ],
        ];
    }
}
