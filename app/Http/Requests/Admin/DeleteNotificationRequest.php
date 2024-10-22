<?php

namespace App\Http\Requests\Admin;

use App\Models\Notification;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class DeleteNotificationRequest extends FormRequest
{
    protected $notification;

    public function __construct(Notification $notification)
    {
        $this->notification = $notification;
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        // Allow non-admins to make the request
        return $user && $user->hasRole('admin');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [];
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return void
     */
    protected function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($this->notification->notificationSettings()->exists()) {
                $validator->errors()->add('notification', 'The notification has associated notification settings and cannot be deleted.');
            }
        });
    }

    /**
     * Custom response for failed validation.
     *
     * @return void
     */
    protected function failedValidation($validator)
    {
        throw new ValidationException($validator, response()->json([
            'errors' => $validator->errors(),
        ], 422));
    }
}
