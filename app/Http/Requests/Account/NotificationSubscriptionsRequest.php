<?php

namespace App\Http\Requests\Account;

use App\Models\AppNotification;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class NotificationSubscriptionsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        if (!$user) {
            return false;
        }

        // Fetch notifications based on IDs to ensure authorization for each notification
        $notificationIds = $this->input('notificationIds', []);
        $notifications = AppNotification::whereIn('id', $notificationIds)->get();

        foreach ($notifications as $notification) {
            if (
                ($notification->is_admin && $user->hasRole('admin')) ||
                ($notification->is_supplier && $user->hasRole('supplier')) ||
                ($notification->is_pharmacy && $user->hasRole('pharmacy')) ||
                ($notification->is_vendor && $user->hasRole('vendor'))
            ) {
                continue;
            }

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
            'notification_ids' => $this->input('notificationIds'),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'notification_ids' => ['array'],
            'notification_ids.*' => ['sometimes', Rule::exists(AppNotification::class, 'id')],
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
