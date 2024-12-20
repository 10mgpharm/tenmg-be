<?php

namespace App\Http\Requests\Admin;

use App\Enums\StatusEnum;
use App\Models\EcommerceMedicationType;
use App\Models\EcommercePackage;
use App\Models\EcommercePresentation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEcommerceMedicationVariationRequest extends FormRequest
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
            'status' => $this->input('status') !== StatusEnum::ACTIVE->value
            ? $this->input('status')
            : StatusEnum::APPROVED->value,
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
            'medication_type_name' => ['sometimes', 'nullable', 'string', 'max:255', Rule::exists(EcommerceMedicationType::class, 'name')],
            'presentation_name' => ['sometimes', 'nullable', 'string', 'max:255', Rule::exists(EcommercePresentation::class, 'name')],
            'package_name' => ['sometimes', 'nullable', 'string', 'max:255', Rule::exists(EcommercePackage::class, 'name')],
            'strength_value' => ['sometimes', 'nullable', 'string', 'max:255'],
            'weight' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'status' => [
                'sometimes',
                'nullable',
                'string',
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
