<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GuestRejectInviteRequest extends FormRequest
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
            'invite_id' => $this->query('inviteId'),
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
            'invite_id' => [
                'required',
                Rule::exists('invites', 'id')->where(
                    fn ($query) => $query
                        ->where('invite_token', $this->query('inviteToken'))
                        ->where('status', 'INVITED')
                ),
            ],
            'invite_token' => ['required', 'string'],
        ];
    }

    /**
     * Custom error messages for validation failures.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'invite_id.exists' => 'The invitation is invalid, expired, or has already been used.',
        ];
    }
}
