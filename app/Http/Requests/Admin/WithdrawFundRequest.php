<?php

namespace App\Http\Requests\Admin;

use App\Enums\OtpType;
use App\Models\EcommerceBankAccount;
use App\Models\EcommerceWallet;
use App\Models\TenMgWallet;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WithdrawFundRequest extends FormRequest
{
    /**
     * @var \App\Models\User The authenticated user making the request.
     */
    protected $user;

    /**
     * @var \App\Models\Business|null The business associated with the authenticated user.
     * This can be either the user's owner business type or the first business owned by the user.
     */
    protected $business;

    /**
     * @var \App\Models\EcommerceWallet|null The wallet associated with the business.
     * This holds the latest wallet of the business, used to check the available balance for withdrawal.
     */
    protected $wallet;

    /**
     * @var \App\Models\EcommerceBankAccount|null The bank account associated with the business.
     * This is used to check if the business has a valid bank account for processing the withdrawal.
     */
    protected $bankAccount;

    /**
     * Initialize to initialize user, business, wallet, and bank account.
     */
    protected function _initialize()
    {

        $this->user = $this->user();

        // Determine the business associated with the user. This will either be the owner's business type or the first business the user belongs to.
        $this->business = $this->user->ownerBusinessType
            ?: $this->user->businesses()->firstWhere('user_id', $this->user->id);

        // Get the latest wallet associated with the business
        $this->wallet = TenMgWallet::latest('id')
            ->first();

        // If the wallet does not exist, create a new one with default values
        // This is a fallback to ensure that the business has a wallet to work with.
        if (!$this->wallet && $this->business) {
            $this->wallet = TenMgWallet::create([
                'business_id' => $this->business->id,
                'previous_balance' => 0,
                'current_balance' => 0,
            ]);
        }


        // Retrieve the bank account associated with the business
        $this->bankAccount = EcommerceBankAccount::where('supplier_id', $this->business->id)
            ->first();
    }

    /**
     * Determine if the user is authorized to make this request.
     * Checks if the user exists, has the correct role, and if the bank account is valid.
     * 
     * @return bool
     */
    public function authorize(): bool
    {
        $this->_initialize();

        // If the user is not authenticated, return false
        if (!$this->user) {
            return false;
        }

        // Check if the user has a valid role and if the bank account exists for the business
        if (
            !($this->user->hasRole('admin')) ||
            !$this->bankAccount || !$this->business
        ) {
            return false;
        }

        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     * This includes validating the withdrawal amount based on the user's wallet balance.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'amount' => [
                'required',
                'numeric',
                'min:1',
                'max:' . $this->wallet->current_balance,
            ],
            'otp' => [
                'required',
                'string',
                'string',
                Rule::exists('otps', 'code')
                    ->where('type', OtpType::WITHDRAW_FUND_TO_BANK_ACCOUNT->value)
                    ->where('user_id', $this->user()->id),
            ],
        ];
    }


    /**
     * Custom validation messages for the request.
     * This includes specific messages for the withdrawal amount field.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'amount.required' => 'The withdrawal amount is required.',
            'amount.numeric' => 'The withdrawal amount must be a valid number.',
            'amount.min' => 'The withdrawal amount must be at least 1.',
            'amount.max' => 'The withdrawal amount cannot exceed your current wallet balance of :max.',
        ];
    }

    /**
     * Custom response for failed authorization.
     * This method is called when the authorization fails and provides a custom response.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function failedAuthorization()
    {
        // If the user is not authenticated, return an "Unauthenticated" error response
        if (!$this->user) {
            abort(response()->json([
                'message' => 'Unauthenticated.',
            ], 401));
        }

        // If the user does not have the required role admin, return an "Unauthorized" error
        if (!($this->user->hasRole('admin'))) {
            abort(response()->json([
                'message' => 'You are not authorized to perform this action.',
            ], 403));
        }
        // If the user does not have a valid business, return an "Unauthorized" error
        if (!$this->business) {
            abort(response()->json([
                'message' => 'You need to belong to a business to withdraw funds.',
            ], 403));
        }

        // If the bank account associated with the business does not exist, return an "Unauthenticated" error
        if (!$this->bankAccount) {
            abort(response()->json([
                'message' => 'Ops, you need to add a bank account to withdraw funds.',
            ], 403));
        }
    }
}
