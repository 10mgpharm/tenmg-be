<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DeleteInviteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        $invite = $this->route('invite');

        // Ensure the invite is created by the user's business and has not been accepted
        if ($user && 
            $invite->business_id === ($user->ownerBusinessType->id ?? $user->businesses()->first()?->id) && 
            $invite->status !== 'ACCEPTED') {
            return true;
        }

        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            //
        ];
    }

    /**
     * Custom response for failed authorization.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function failedAuthorization()
    {
        $invite = $this->route('invite');

        if ($invite->status === 'ACCEPTED') {
            abort(response()->json([
                'message' => 'This invite has already been accepted and cannot be deleted.',
            ], 403));
        }

        abort(response()->json([
            'message' => 'You are not authorized to delete this resource.',
        ], 403));
    }
}
