<?php

namespace App\Http\Requests\Supplier;

use Illuminate\Foundation\Http\FormRequest;

class DashboardRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        // First, check if the user is authenticated
        if (! $user) {
            return false;
        }

        // Then, check if the user is a supplier.
        $entityType = $user->ownerBusinessType?->type ?? $user->businesses()->firstWhere('user_id', $this->id)?->type;
        if ( $entityType !== 'SUPPLIER') {
            return false;
        }

        return true;
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
}
