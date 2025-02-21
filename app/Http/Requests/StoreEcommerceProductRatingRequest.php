<?php

namespace App\Http\Requests;

use App\Models\EcommerceProduct;
use App\Models\EcommerceProductRating;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEcommerceProductRatingRequest extends FormRequest
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
            'rating' => $this->input('rating'),
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
                Rule::unique(EcommerceProductRating::class, 'ecommerce_product_id')
                    ->where(fn($query) => $query->where('user_id', $this->user()->id)),
            ],
            'rating' => ['required', 'numeric', 'min:0', 'max:5'],
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
