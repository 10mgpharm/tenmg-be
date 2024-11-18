<?php

namespace App\Http\Requests\Admin;

use App\Models\EcommerceProduct;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEcommerceProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        return $user && ($user->hasRole('admin') || $user->hasRole('supplier'));
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
            'thumbnailFile' => $this->file('thumbnailFile'),
            'status' => $this->user()->hasRole('admin') ? ($this->status ?? 'ACTIVE') : 'DRAFTED',
            // 'ecommerce_variation' => $this->input('ecommerceVariation'),
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
            'name' => ['required', 'string', 'max:255', Rule::unique(EcommerceProduct::class)],
            'category_name' => ['required', 'string', 'max:255'],
            'brand_name' => ['required', 'string', 'max:255'],
            'medication_type_name' => ['required', 'string', 'max:255'],
            'quantity' => ['required', 'integer', 'min:1'],
            'actual_price' =>['required', 'numeric', 'min:0'],
            'discount_price' => ['nullable', 'numeric', 'min:0'],
            'min_delivery_duration' => ['required', 'integer', 'min:0'],
            'max_delivery_duration' => ['required', 'integer', 'min:0'],
            'expired_at' => ['required', 'date'],
            'thumbnailFile' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png,gif',
                'max:10240',
            ],
            'status' => ['nullable', Rule::in(['ACTIVE', 'DRAFTED'])],
            'productEssential' => ['nullable', 'string', 'min:3'],
            'startingStock' => ['nullable', 'numeric', 'min:0'],
            'currentStock' => ['nullable', 'numeric', 'min:0'],
            'stockStatus' => ['nullable', Rule::in(['AVAILABLE', 'UNAVAILABLE'])],
            // 'ecommerce_variation' => ['required', 'string', 'max:255'],
        ];
    }
}
