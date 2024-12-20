<?php

namespace App\Http\Requests\Admin;

use App\Models\EcommercePackage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEcommercePackageRequest extends FormRequest
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
        $package = $this->route('package');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique(EcommercePackage::class)->ignore($package->id),
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
