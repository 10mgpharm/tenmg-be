<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListInvitesRequest extends FormRequest
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
    
        // Check if the user owns a business or belongs to a business as specified
        $ownsBusinessOrBelongsToBusiness = $user->ownerBusinessType || $user->businesses()->firstWhere('user_id', $this->id);
    
        // Check if the user has the required roles
        $hasRequiredRole = $user->hasRole('vendor') || $user->hasRole('admin');
    
        return $ownsBusinessOrBelongsToBusiness && $hasRequiredRole;
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
    
        if (!$user->ownerBusinessType && !$user->businesses()->firstWhere('user_id', $this->id)) {
            abort(response()->json([
                'message' => 'You must own or belong to a business to create this resource.',
            ], 403));
        }
    
        if (!$user->hasRole('vendor') && !$user->hasRole('admin')) {
            abort(response()->json([
                'message' => 'You do not have the required role to create this resource.',
            ], 403));
        }
    
        abort(response()->json([
            'message' => 'You are not authorized to create this resource.',
        ], 403));
    }
}
