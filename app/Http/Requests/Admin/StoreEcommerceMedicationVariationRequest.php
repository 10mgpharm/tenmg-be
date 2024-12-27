<?php

namespace App\Http\Requests\Admin;

use App\Models\EcommerceMeasurement;
use App\Models\EcommerceMedicationType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\EcommercePresentation;

class StoreEcommerceMedicationVariationRequest extends FormRequest
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
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'medication_type_name' => $this->input('medicationTypeName'),
            'presentation_name' => $this->input('presentationName'),
            'measurement_name' => $this->input('measurementName'),
            'strength_value' => $this->input('strengthValue'),
            'package_per_roll' => $this->input('packagePerRoll'),
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
            'medication_type_name' => ['required', 'string', 'max:255', Rule::exists(EcommerceMedicationType::class, 'name')],
            'presentation_name' => ['required', 'string', 'max:255', Rule::exists(EcommercePresentation::class, 'name')],
            'measurement_name' => ['required', 'string', 'max:255', Rule::exists(EcommerceMeasurement::class, 'name')],
            'strength_value' => ['required', 'string', 'max:255',],
            'package_per_roll' => ['sometimes', 'nullable', 'string', 'max:255'],
            'weight' => ['required', 'numeric', 'min:0'],
            'active' => ['sometimes', 'boolean',]
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
            'message' => 'You are not authorized to create this resource.',
        ], 403));
    }
}
