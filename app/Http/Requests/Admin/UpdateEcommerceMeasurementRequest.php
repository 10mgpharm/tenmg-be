<?php

namespace App\Http\Requests\Admin;

use App\Enums\StatusEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\EcommerceMeasurement;
use Illuminate\Validation\Rules\Enum;

class UpdateEcommerceMeasurementRequest extends FormRequest
{/**
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
        $measurement = $this->route('measurement');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique(EcommerceMeasurement::class)->ignore($measurement->id)
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

    /**
     * Custom response for failed authorization.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function failedAuthorization()
    {
        abort(response()->json([
            'message' => 'You are not authorized to update this resource.',
        ], 403));
    }
}
