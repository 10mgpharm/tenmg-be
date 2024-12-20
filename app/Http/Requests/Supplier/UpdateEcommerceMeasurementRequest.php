<?php

namespace App\Http\Requests\Supplier;

use App\Models\EcommerceMeasurement;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEcommerceMeasurementRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        $measurement = $this->route('measurement');

        // Suppliers can only update categories created by their business
        if ($user->hasRole('supplier') && $measurement->business_id === ($user->ownerBusinessType->id ?: $user->businesses()->first()->id)) {
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
        $measurement = $this->route('measurement');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique(EcommerceMeasurement::class)->ignore($measurement->id),
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
