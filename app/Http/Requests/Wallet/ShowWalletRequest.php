<?php

namespace App\Http\Requests\Wallet;

use App\Models\Wallet;
use Illuminate\Foundation\Http\FormRequest;

class ShowWalletRequest extends FormRequest
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

        $wallet = Wallet::find($this->route('id'));

        if (! $wallet) {
            return false;
        }

        // Check if user owns the business that owns the wallet
        $business = $user->ownerBusinessType
            ?: $user->businesses()->firstWhere('user_id', $user->id);

        return $wallet->business_id === $business?->id;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'id' => ['required', 'uuid', 'exists:wallets,id'],
        ];
    }
}
