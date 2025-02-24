<?php

namespace App\Http\Requests;

use App\Models\EcommerceProduct;
use App\Models\EcommerceProductRating;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ShowEcommerceProductRatingRequest extends FormRequest
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
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            //
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
            'message' => 'You are not authorized to view this resource.',
        ], 403));
    }
}
