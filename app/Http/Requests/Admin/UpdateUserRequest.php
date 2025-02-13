<?php

namespace App\Http\Requests\Admin;

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
        $user = $this->user();

        return $user && $user->hasRole('admin');
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
        abort(response()->json([
            'message' => 'You are not authorized to update this resource.',
        ], 403));
    }
}
