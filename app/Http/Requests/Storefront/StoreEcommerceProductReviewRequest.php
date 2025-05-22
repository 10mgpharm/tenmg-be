<?php

namespace App\Http\Requests\Storefront;

use App\Models\EcommerceProduct;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEcommerceProductReviewRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        return $user && $user->hasRole('customer');
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'ecommerce_product_id' => $this->input('productId'),
            'name' => $this->input('name'),
            'email' => $this->input('email'),
            'comment' => $this->input('comment'),
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
            'ecommerce_product_id' => [
                'required',
                Rule::exists(EcommerceProduct::class, 'id'),
                Rule::unique('ecommerce_product_reviews', 'ecommerce_product_id')
                    ->where(fn($query) => $query->where('user_id', $this->user()->id)),
            ],
            'name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'email' => ['sometimes', 'nullable', 'string', 'email'],
            'comment' => ['required', 'string', 'max:255'],
        ];
    }


    /**
     * Custom error messages for validation failures.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'ecommerce_product_id.required' => 'The product ID is required.',
            'ecommerce_product_id.exist' => 'The product ID does not exist.',
            'ecommerce_product_id.unique' => 'You have previously reviewed this product.',
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
