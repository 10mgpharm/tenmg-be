<?php

namespace App\Http\Requests\Transaction;

use App\Enums\TransactionCategory;
use App\Models\Wallet;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class CreateTransactionRequest extends FormRequest
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

        $wallet = Wallet::find($this->input('walletId'));

        if (! $wallet) {
            return false;
        }

        // Check if user owns the business that owns the wallet
        $business = $user->ownerBusinessType
            ?: $user->businesses()->firstWhere('user_id', $user->id);

        return $wallet->business_id === $business?->id;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'wallet_id' => $this->input('walletId'),
            'currency_id' => $this->input('currencyId'),
            'transaction_category' => $this->input('transactionCategory'),
            'transaction_type' => $this->input('transactionType'),
            'transaction_method' => $this->input('transactionMethod'),
            'transaction_reference' => $this->input('transactionReference'),
            'transaction_narration' => $this->input('transactionNarration'),
            'transaction_description' => $this->input('transactionDescription'),
            'processor' => $this->input('processor'),
            'processor_reference' => $this->input('processorReference'),
            'beneficiary_id' => $this->input('beneficiaryId'),
            'transaction_data' => $this->input('transactionData'),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'wallet_id' => ['required', 'uuid', 'exists:wallets,id'],
            'currency_id' => ['required', 'uuid', 'exists:currencies,id'],
            'transaction_category' => ['required', new Enum(TransactionCategory::class)],
            'transaction_type' => ['required', 'string', 'max:255'],
            'transaction_method' => ['nullable', 'string', 'max:255'],
            'transaction_reference' => ['nullable', 'string', 'max:255'],
            'transaction_narration' => ['nullable', 'string', 'max:255'],
            'transaction_description' => ['nullable', 'string'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'processor' => ['nullable', 'uuid', 'exists:service_providers,id'],
            'processor_reference' => ['nullable', 'string', 'max:255'],
            'beneficiary_id' => ['nullable', 'uuid'],
            'status' => ['nullable', 'string'],
            'transaction_data' => ['nullable', 'array'],
        ];
    }
}
