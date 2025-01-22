<?php

namespace App\Http\Requests\Admin;

use App\Enums\StatusEnum;
use App\Models\EcommerceProduct;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class StoreEcommerceProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        return $user && ($user->hasRole('admin'));
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'product_name' => $this->input('productName'),
            'product_description' => $this->input('productDescription'),
            'category_name' => $this->input('categoryName'),
            'brand_name' => $this->input('brandName'),
            
            'medication_type_name' => $this->input('medicationTypeName'),
            'measurement_name' => $this->input('measurementName'),
            'presentation_name' => $this->input('presentationName'),
            'package_per_roll' => $this->input('packagePerRoll'),
            'strength_value' => $this->input('strengthValue'),

            'actual_price' => $this->input('actualPrice'),
            'discount_price' => $this->input('discountPrice'),
            'low_stock_level' => $this->input('lowStockLevel'),
            'out_stock_level' => $this->input('outStockLevel'),
            'expired_at' => $this->input('expiredAt'),
            'thumbnailFile' => $this->file('thumbnailFile'),
            'status' => $this->status ?? StatusEnum::INACTIVE->value,
            // 'ecommerce_variation' => $this->input('ecommerceVariation'),

            // ProductDetail model attributes
            'essential' => $this->input('productEssential'),
            'starting_stock' => $this->input('startingStock'),
            'current_stock' => $this->input('currentStock'),
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
            // Product Basic
            'product_name' => ['required', 'string', 'max:255', Rule::unique(EcommerceProduct::class, 'name')],
            'product_description' => ['required', 'string', 'max:255'],
            'category_name' => ['required', 'string', 'max:255'],
            'brand_name' => ['required', 'string', 'max:255'],
            'thumbnailFile' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png,gif',
                'max:10240',
            ],
            
            // Product Essentials
            'medication_type_name' => ['required', 'string', 'max:255'],
            'measurement_name' => ['required', 'string', 'max:255'],
            'presentation_name' => ['required', 'string', 'max:255'],
            'package_per_roll' => ['required', 'string', 'max:255'],
            'strength_value' => ['required',  'string', 'max:255'],
            'weight' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'actual_price' => ['required', 'numeric', 'min:0'],
            'discount_price' => ['nullable', 'numeric', 'min:0', 'lt:actual_price'],

            // Product Inventory
            'quantity' => ['required', 'integer', 'min:1'],
            'low_stock_level' => ['nullable', 'sometimes', 'integer', 'min:0', 'gte:out_stock_level'],
            'out_stock_level' => ['nullable', 'sometimes', 'integer', 'min:0', 'lte:low_stock_level'],
            'expired_at' => ['required', 'date', 'after:today'],

            'status' => ['nullable', 'sometimes', new Enum(StatusEnum::class)],
            'active' => [
                'sometimes',
                'boolean',
            ],

            // 'min_delivery_duration' => ['required', 'integer', 'min:0'],
            // 'max_delivery_duration' => ['required', 'integer', 'min:0'],

            // ProductDetails model attributes
            'essential' => ['nullable', 'sometimes', 'string', 'min:3'],
            'starting_stock' => ['nullable', 'sometimes', 'numeric', 'min:0'],
            'current_stock' => ['nullable', 'sometimes', 'numeric', 'min:0'],

            // 'stockStatus' => ['nullable', Rule::in(['AVAILABLE', 'UNAVAILABLE'])],

            // 'ecommerce_variation' => ['required', 'string', 'max:255'],
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
