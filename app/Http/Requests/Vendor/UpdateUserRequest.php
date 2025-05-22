<?php

namespace App\Http\Requests\Vendor;

use App\Enums\StatusEnum;
use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateUserRequest extends FormRequest
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
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $user = $this->user();
        $roleName =  $user->getRoleNames()->first();

        if($this->input('role')){
            if ($this->input('role') == 'admin') {
                $this->merge([
                    'role' => $roleName,
                ]);
            }
            $role = Role::where('name', $this->input('role'))
                ->whereIn('name', ['admin', 'vendor', 'support', 'operation'])
                ->first();
    
            $this->merge([
                'role_id' => $role ? $role->id : null, // Merge the role ID or null if not found
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'role_id' => ['sometimes', 'nullable', 'exists:roles,id'],
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
                'message' => 'You are not authorized to update same resource.',
            ], 403));
        }

        if (!$user->hasRole('vendor')) {
            abort(response()->json([
                'message' => 'You do not have the required role to update this resource.',
            ], 403));
        }
    
        if (!$user->ownerBusinessType) {
            abort(response()->json([
                'message' => 'You must own the business to update this resource.',
            ], 403));
        }
    
        abort(response()->json([
            'message' => 'You are not authorized to update this resource.',
        ], 403));
    }
}
