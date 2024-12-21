<?php

namespace App\Http\Requests\Admin;

use App\Enums\StatusEnum;
use App\Models\EcommerceProduct;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class UpdateEcommerceProductRequest extends FormRequest
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
            'medication_type_name' => $this->input('medicationTypeName'),
            'category_name' => $this->input('categoryName'),
            'brand_name' => $this->input('brandName'),

            'measurement_name' => $this->input('measurementName'),
            'presentation_name' => $this->input('presentationName'),
            'package_name' => $this->input('packageName'),
            'strength_value' => $this->input('strengthValue'),

            'actual_price' => $this->input('actualPrice'),
            'discount_price' => $this->input('discountPrice'),
            'low_stock_level' => $this->input('lowStockLevel'),
            'out_stock_level' => $this->input('outStockLevel'),
            'expired_at' => $this->input('expiredAt'),
            'thumbnailFile' => $this->file('thumbnailFile'),
            'status' => $this->status ?? StatusEnum::ACTIVE->value,
            'status_comment' => $this->input('comment'),

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

            // Product Basic
            'product_name' => ['sometimes', 'nullable', 'string', 'max:255', Rule::unique(EcommerceProduct::class, 'name')->ignore($product->id)],
            'product_description' => ['sometimes',  'nullable', 'string', 'max:255',],
            'medication_type_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'category_name' => ['sometimes',  'nullable', 'string', 'max:255'],
            'brand_name' => ['sometimes',  'nullable', 'string', 'max:255'],
            'thumbnailFile' => [
                'sometimes',
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,gif',
                'max:10240',
            ],

            // Product Essentials
            'measurement_name' => ['sometimes',  'nullable', 'string', 'max:255'],
            'presentation_name' => ['sometimes',  'nullable', 'string', 'max:255'],
            'package_name' => ['sometimes',  'nullable', 'string', 'max:255'],
            'strength_value' => ['sometimes',  'nullable', 'string', 'max:255'],
            'weight' => ['sometimes',  'nullable', 'numeric', 'min:0'],

            // Product Inventory
            'quantity' => ['sometimes',  'nullable', 'integer', 'min:1'],
            'low_stock_level' => ['nullable', 'sometimes', 'integer', 'min:0', 'gte:out_stock_level'],
            'out_stock_level' => ['nullable', 'sometimes', 'integer', 'min:0', 'lte:low_stock_level'],
            'actual_price' => ['sometimes',  'nullable', 'numeric', 'min:0'],
            'discount_price' => ['sometimes', 'nullable', 'numeric', 'min:0', 'lt:actual_price'],
            'expired_at' => ['sometimes', 'nullable', 'date'],

            'status' => ['sometimes',  'nullable', new Enum(StatusEnum::class),],
            'statusComment' => ['required_if:status,' . implode(',', [
                StatusEnum::REJECTED->value,
                StatusEnum::INACTIVE->value,
                StatusEnum::SUSPENDED->value,
                StatusEnum::FLAGGED->value,
            ]),],
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
