<?php

namespace App\Http\Requests\Supplier;

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
        $product = $this->route('product');

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
        $product = $this->route('product');

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
            'status' =>  $this->input('status') !== StatusEnum::ACTIVE->value
            ? $this->input('status')
            : $product->status,
            'status_comment' => $this->input('comment'),

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
        // Retrieve the current medication type from the route
        $product = $this->route('product');

        return [

            // Product Basic
            'product_name' => ['sometimes', 'nullable', 'string', 'max:255', Rule::unique(EcommerceProduct::class, 'name')->ignore($product->id)],
            'product_description' => ['sometimes',  'nullable', 'string', 'max:255',],
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
            'medication_type_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'measurement_name' => ['sometimes',  'nullable', 'string', 'max:255'],
            'presentation_name' => ['sometimes',  'nullable', 'string', 'max:255'],
            'package_per_roll' => ['sometimes',  'nullable', 'string', 'max:255'],
            'strength_value' => ['sometimes',  'nullable', 'string', 'max:255',],
            'weight' => ['sometimes',  'nullable', 'numeric', 'min:0'],
            'actual_price' => ['sometimes',  'nullable', 'numeric', 'min:0'],
            'discount_price' => ['sometimes', 'nullable', 'numeric', 'min:0', 'lt:actual_price'],

            // Product Inventory
            'quantity' => ['sometimes',  'nullable', 'integer', 'min:1'],
            'low_stock_level' => ['nullable', 'sometimes', 'integer', 'min:0', 'gte:out_stock_level'],
            'out_stock_level' => ['nullable', 'sometimes', 'integer', 'min:0', 'lte:low_stock_level'],
            'expired_at' => ['sometimes', 'nullable', 'date'],

            'status' => ['sometimes',  'nullable', new Enum(StatusEnum::class),],
            'statusComment' => ['sometimes', 'nullable', 'string',],
            'active' => [
                'sometimes',
                'boolean',
            ],


            // ProductDetail model attributes
            'essential' => ['nullable', 'sometimes', 'string', 'min:3'],
            'starting_stock' => ['nullable', 'sometimes', 'numeric', 'min:0'],
            'current_stock' => ['nullable', 'sometimes', 'numeric', 'min:0'],
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
