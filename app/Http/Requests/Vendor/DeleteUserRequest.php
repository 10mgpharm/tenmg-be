<?php

namespace App\Http\Requests\Vendor;

use App\Enums\StatusEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class DeleteUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {

        $admin = $this->user();
    
        if (!$admin) {
            return false; // Ensure there is a logged-in user
        }

        $_user = $this->route('user');

        if(
            $_user->businesses()->first()?->id !== ($admin->ownerBusinessType?->id ?? $admin->businesses()->first()?->id) ||
            $_user->id == $admin->id
            ){
            return false;
        }
        
        return $admin->ownerBusinessType && $admin->hasRole('vendor');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', 'string', new Enum(StatusEnum::class),],
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
        $_user = $this->route('user');

        if (!$user) {
            abort(response()->json([
                'message' => 'Unauthenticated.',
            ], 403));
        }

        if ($user->id === $_user->id) {
            abort(response()->json([
                'message' => 'You are not authorized to delete same resource.',
            ], 403));
        }

        if (!$user->hasRole('vendor')) {
            abort(response()->json([
                'message' => 'You do not have the required role to delete this resource.',
            ], 403));
        }
    
        if (!$user->ownerBusinessType) {
            abort(response()->json([
                'message' => 'You must own the business to delete this resource.',
            ], 403));
        }
    
        abort(response()->json([
            'message' => 'You are not authorized to delete this resource.',
        ], 403));
    }
}
