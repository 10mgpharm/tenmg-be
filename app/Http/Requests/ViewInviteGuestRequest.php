<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ViewInviteGuestRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'invite_token' => $this->query('inviteToken'),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'invite_token' => ['required', 'string', 'exists:invites,invite_token'],
        ];
    }
}
