<?php

namespace App\Http\Requests\Vendor;

use Illuminate\Foundation\Http\FormRequest;

class ShowUsersRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
    
        if (!$user) {
            return false; // Ensure there is a logged-in user
        }
        
        return $user->ownerBusinessType && $user->hasRole('vendor');
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
        $user = $this->user();

        if (!$user) {
            abort(response()->json([
                'message' => 'Unauthenticated.',
            ], 403));
        }

        if (!$user->hasRole('vendor')) {
            abort(response()->json([
                'message' => 'You do not have the required role to view this resource.',
            ], 403));
        }
    
        if (!$user->ownerBusinessType) {
            abort(response()->json([
                'message' => 'You must own the business to view this resource.',
            ], 403));
        }
    
        abort(response()->json([
            'message' => 'You are not authorized to view this resource.',
        ], 403));
    }
}
