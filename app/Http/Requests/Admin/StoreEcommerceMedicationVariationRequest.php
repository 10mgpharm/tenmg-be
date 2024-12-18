<?php

namespace App\Http\Requests\Admin;

use App\Models\EcommerceMedicationType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\EcommercePackage;
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
            'package_name' => $this->input('packageName'),
            'strength_value' => $this->input('strengthValue'),
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
            'package_name' => ['required', 'string', 'max:255', Rule::exists(EcommercePackage::class, 'name')],
            'strength_value' => ['required', 'string', 'max:255',],
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
