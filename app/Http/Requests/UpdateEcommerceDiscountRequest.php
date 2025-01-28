<?php

namespace App\Http\Requests;

use App\Enums\DiscountApplicationMethodEnum;
use App\Enums\DiscountCustomerLimitEnum;
use App\Enums\DiscountTypeEnum;
use App\Models\EcommerceDiscount;
use App\Models\EcommerceProduct;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class UpdateEcommerceDiscountRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        $discount = $this->route('discount');

        // Can only update discount created by their business
        if ($user && $discount->business_id === ($user->ownerBusinessType->id ?? $user->businesses()->first()?->id)) {
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
            'application_method' => $this->input('applicationMethod'),
            'coupon_code' => $this->input('couponCode'),
            'type' => $this->input('discountType'),
            'amount' => $this->input('discountAmount'),
            'applicable_products' => $this->input('applicableProducts'),
            'customer_limit' => $this->input('customerLimit'),
            'start_date' => $this->input('startDate'),
            'end_date' => $this->input('endDate'),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $user = $this->user();
        $business_id = $user->ownerBusinessType?->id ?: $user->businesses()
            ->firstWhere('user_id', $user->id)?->id;
        $discount = $this->route('discount');

        return [
            'application_method' => ['sometimes', 'nullable', new Enum(DiscountApplicationMethodEnum::class),],
            'coupon_code' => ['sometimes', 'nullable', 'string', 'min:3', 'max:255', Rule::unique(EcommerceDiscount::class, 'coupon_code')->where('business_id', $business_id)->ignore($discount->id)],
            'type' => [
                'sometimes',
                'nullable',
                new Enum(DiscountTypeEnum::class),
                function ($attribute, $value, $fail) use ($discount) {
                    $type = $value ?? $discount->type;
                    $amount = $this->input('amount') ?? $discount->amount;

                    // Check if the discount type is percentage and the value exceeds 100
                    if ($type === DiscountTypeEnum::PERCENTAGE->value && $amount > 100) {
                        $fail('The discount value must be between 0 and 100 for percentage discounts.');
                    }
                },
            ],
            'amount' => [
                'sometimes',
                'nullable',
                'min:0',
                'numeric',
                function ($attribute, $value, $fail) use ($discount) {
                    $amount = $value ?? $discount->amount;
                    $type = $this->input('type') ?? $discount->type;

                    // Check if the discount type is percentage and the value exceeds 100
                    if ($type === DiscountTypeEnum::PERCENTAGE->value && $amount > 100) {
                        $fail('The discount value must be between 0 and 100 for percentage discounts.');
                    }
                },
            ],
            'applicable_products' => [
                'nullable',
                'sometimes',
                'array',
                function ($attribute, $value, $fail) use ($business_id) {
                    $invalidProducts = collect($value)
                        ->filter(fn($productId) => !EcommerceProduct::where('id', $productId)->where('business_id', $business_id)->exists());

                    if ($invalidProducts->isNotEmpty()) {
                        $fail("Some products do not belong to the current business.");
                    }
                },
            ],
            'customer_limit' => ['sometimes', 'nullable', new Enum(DiscountCustomerLimitEnum::class),],
            'start_date' => ['sometimes', 'nullable', 'date', 'after_or_equal:today'],
            'end_date' => ['sometimes', 'nullable', 'date', 'after_or_equal:start_date',],
            'status' => ['sometimes', 'nullable', 'in:ACTIVE,INACTIVE'],
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
