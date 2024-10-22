<?php

namespace App\Http\Requests\Account;

use Illuminate\Foundation\Http\FormRequest;

class NotificationSubscriptionRequest extends FormRequest
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

        $notification = $this->route('notification');

        if ($notification->is_admin && $user->hasRole('admin')) {
            return true;
        }

        if ($notification->is_supplier && $user->hasRole('supplier')) {
            return true;
        }

        if ($notification->is_pharmacy && $user->hasRole('pharmacy')) {
            return true;
        }

        if ($notification->is_vendor && $user->hasRole('vendor')) {
            return true;
        }

        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            // Validation rules can be added here if needed
        ];
    }

    /**
     * Custom error message for unauthorized access.
     */
    public function messages(): array
    {
        return [
            'authorize' => 'You are not authorized to subscribe to this notification.',
        ];
    }
}
