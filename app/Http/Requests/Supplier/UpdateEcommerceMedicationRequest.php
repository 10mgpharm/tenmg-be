<?php

namespace App\Http\Requests\Supplier;

use App\Models\EcommerceMedicationType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEcommerceMedicationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        $medication_type = $this->route('medication_type');

        // Suppliers can only update products created by their business
        if ($user && $user->hasRole('supplier') && $medication_type && $medication_type->business_id === $user->ownerBusinessType?->id) {
            return true;
        }

        return false;
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
                Rule::unique(EcommerceMedicationType::class)->ignore($medication_type->id),
            ],
            'status' => [
                'sometimes',
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
