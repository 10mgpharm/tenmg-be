<?php

namespace App\Http\Requests\Supplier;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\EcommerceBankAccount;
use Illuminate\Validation\Rule;

class GetBankAccountRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        $business = $user->ownerBusinessType
        ?: $user->businesses()->firstWhere('user_id', $user->id);

        // First, check if the user is authenticated
        if (! $user) {
            return false;
        }

        // Then, check the user role
        if (($user->hasRole('supplier') || $user->hasRole('vendor') || $user->hasRole('admin'))) {
            return true;
        }

        return false;
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
        
        if ($user->hasRole('supplier') || $user->hasRole('vendor') || $user->hasRole('admin')) {
            abort(response()->json([
                'message' => 'You are not authorized to add bank accounts.',
            ], 403));
        }

    }
}
