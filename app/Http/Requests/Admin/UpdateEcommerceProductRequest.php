<?php

namespace App\Http\Requests\Admin;

use App\Models\EcommerceProduct;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEcommerceProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        $product = $this->route('product');

        if ($user->hasRole('admin')) {
            return true;
        }

        // Suppliers can only update products created by their business
        if ($user->hasRole('supplier') && $product->business_id === $user->ownerBusinessType->id) {
            return true;
        }

        return false;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'category_name' => $this->input('categoryName'),
            'brand_name' => $this->input('brandName'),
            'medication_type_name' => $this->input('medicationTypeName'),
            'actual_price' => $this->input('actualPrice'),
            'discount_price' => $this->input('discountPrice'),
            'min_delivery_duration' => $this->input('minDeliveryDuration'),
            'max_delivery_duration' => $this->input('maxDeliveryDuration'),
            'expired_at' => $this->input('expiredAt'),
            'status' => $this->user()->hasRole('admin')
                ? ($this->input('status') ?? 'ACTIVE')
                : (in_array($this->input('status'), ['DRAFTED', 'INACTIVE'])
                    ? $this->input('status')
                    : 'DRAFTED'),

        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // Retrieve the current medication type from the route
        $product = $this->route('product');

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique(EcommerceProduct::class)->ignore($product->id)],
            'category_name' => ['required', 'string', 'max:255'],
            'brand_name' => ['required', 'string', 'max:255'],
            'medication_type_name' => ['required', 'string', 'max:255'],
            'quantity' => ['required', 'integer', 'min:1'],
            'actual_price' => ['required', 'numeric', 'min:0'],
            'discount_price' => ['nullable', 'numeric', 'min:0'],
            'min_delivery_duration' => ['required', 'integer', 'min:0'],
            'max_delivery_duration' => ['required', 'integer', 'min:0'],
            'expired_at' => ['required', 'date'],
            'thumbnailFile' => [
                'sometimes',
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,gif',
                'max:10240',
            ],
            'status' => ['nullable', Rule::in(['ACTIVE', 'INACTIVE', 'SUSPENDED', 'DRAFTED', 'ARCHIVED'])]
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
            'message' => 'You are not authorized to update this product.',
        ], 403));
    }
}
