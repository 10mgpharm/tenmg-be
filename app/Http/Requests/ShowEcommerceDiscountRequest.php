<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ShowEcommerceDiscountRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        $discount = $this->route('discount');

        // Can only view discount created by their business
        if ($user && $discount->business_id === ($user->ownerBusinessType->id ?? $user->businesses()->first()?->id)) {
            return true;
        }

        return false;
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
