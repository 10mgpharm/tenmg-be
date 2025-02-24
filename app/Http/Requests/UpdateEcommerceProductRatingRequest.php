<?php

namespace App\Http\Requests;

use App\Models\EcommerceProduct;
use App\Models\EcommerceProductRating;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEcommerceProductRatingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        $rating = $this->route('rating');
        
        return $user && $user->hasRole('customer') && $rating->user_id === $user->id;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
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
            'rating' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:5'],
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
