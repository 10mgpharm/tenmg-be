<?php

namespace App\Http\Requests\VirtualAccount;

use App\Models\VirtualAccount;
use Illuminate\Foundation\Http\FormRequest;

class UpdateVirtualAccountRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        if (! $user) {
            return false;
        }

        $virtualAccount = VirtualAccount::find($this->route('id'));

        if (! $virtualAccount) {
            return false;
        }

        // Check if user owns the business that owns the virtual account
        $business = $user->ownerBusinessType
            ?: $user->businesses()->firstWhere('user_id', $user->id);

        return $virtualAccount->business_id === $business?->id;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'status' => $this->input('status'),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'id' => ['required', 'uuid', 'exists:virtual_accounts,id'],
            'status' => ['required', 'string', 'in:active,inactive,suspended,pending'],
        ];
    }
}
