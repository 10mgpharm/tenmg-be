<?php

namespace App\Http\Requests;

use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateInviteRequest extends FormRequest
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
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $user = $this->user();
    
        $roleName = $this->user()->roles()->first()?->name;

        if($this->input('role') == 'admin'){
            $this->merge([
                'role' => $roleName,
            ]);
        }
        $role = Role::where('name', $this->input('role'))
            ->whereIn('name', ['admin', 'vendor', 'support', 'operation'])
            ->first();

        $this->merge([
            'full_name' => $this->input('fullName'),
            'role_id' => $role ? $role->id : null, // Merge the role ID or null if not found
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $user = $this->user();
        
        $business_id = $user->ownerBusinessType?->id ?: $user->businesses()->firstWhere('user_id', $this->id)?->id;

        return [
            'full_name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('invites')->where(fn ($query) => $query->where('business_id', $business_id)->whereNotIn('status', ['REJECTED', 'REMOVED'])),
                Rule::unique('users', 'email')
                ->where(fn ($query) => $query->whereExists(fn ($subQuery) =>
                    $subQuery->select('id')
                        ->from('business_users')
                        ->whereColumn('business_users.user_id', 'users.id')
                        ->where('business_users.business_id', $business_id)
                )),

            ],
            'role_id' => ['required', 'exists:roles,id'],
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
            'email.unique' => 'This email has already been invited to this business.',
            'role_id.required' => $this->input('role') ? 'The selected role does not exist.' : 'The role field is required.',
            'role_id.exists' => 'The selected role does not exist.',
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
