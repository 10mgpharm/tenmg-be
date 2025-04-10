<?php

namespace App\Http\Requests\Supplier;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use App\Enums\OtpType;
use App\Models\EcommerceBankAccount;
use Illuminate\Validation\Rule;

class AddBankAccountRequest extends FormRequest
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

        // Then, check if the user is a supplier.
        $entityType = $business?->type;
        if ($entityType !== 'SUPPLIER' || $entityType !== 'VENDOR') {
            return false;
        }

        if(EcommerceBankAccount::where('supplier_id', $business->id)->exists()){
            return false;
        }

        return true;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
{

        $this->merge([
            'bank_name' => $this->input('bankName'),
            'account_name' => $this->input('accountName'),
            'account_number' => $this->input('accountNumber'),
            'bank_code' => $this->input('bankCode'),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'bank_name' => ['required', 'string', 'max:255'],
            'account_number' => ['required', 'digits:10', Rule::unique(EcommerceBankAccount::class, 'account_number')],
            'account_name' => ['required', 'string', 'max:255'],
            'bank_code' => ['required', 'string', 'max:255'],
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
        $business = $user->ownerBusinessType
        ?: $user->businesses()->firstWhere('user_id', $user->id);

        if (!$user) {
            abort(response()->json([
                'message' => 'Unauthenticated.',
            ], 403));
        }

        $entityType = $business?->type;
        if ($entityType !== 'SUPPLIER' || $entityType !== 'VENDOR') {
            abort(response()->json([
                'message' => 'You are not authorized to add bank accounts.',
            ], 403));
        }

        if(EcommerceBankAccount::where('supplier_id', $business->id)->exists()){
            abort(response()->json([
                'message' => 'Ops, you can only add one bank account.',
            ], 403));
        }
    }
}
