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
        $this->merge([
            'category_name' => $this->input('categoryName'),
            'brand_name' => $this->input('brandName'),
            'medication_type_name' => $this->input('medicationTypeName'),
            'actual_price' => $this->input('actualPrice'),
            'discount_price' => $this->input('discountPrice'),
            'min_delivery_duration' => $this->input('minDeliveryDuration'),
            'max_delivery_duration' => $this->input('maxDeliveryDuration'),
            'expired_at' => $this->input('expiredAt'),
            'status' =>in_array($this->input('status'), [StatusEnum::DRAFT->value, StatusEnum::INACTIVE->value, StatusEnum::PENDING->value])
                    ? $this->input('status')
                    : StatusEnum::PENDING->value,
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
            'name' => ['sometimes', 'string', 'max:255', Rule::unique(EcommerceProduct::class)->ignore($product->id)],
            'category_name' => ['sometimes', 'string', 'max:255'],
            'brand_name' => ['sometimes', 'string', 'max:255'],
            'medication_type_name' => ['sometimes', 'string', 'max:255'],
            'quantity' => ['sometimes', 'integer', 'min:1'],
            'actual_price' => ['sometimes', 'numeric', 'min:0'],
            'discount_price' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'min_delivery_duration' => ['sometimes', 'integer', 'min:0'],
            'max_delivery_duration' => ['sometimes', 'integer', 'min:0'],
            'expired_at' => ['sometimes', 'date'],
            'thumbnailFile' => [
                'sometimes',
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,gif',
                'max:10240',
            ],
            'status' => ['sometimes', 'nullable', new Enum(StatusEnum::class)],
            'statusComment' => ['sometimes', 'nullable', 'required_if:status,'.implode(',', [
                StatusEnum::REJECTED->value,
                StatusEnum::INACTIVE->value,
                StatusEnum::SUSPENDED->value,
            ]), ],
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
