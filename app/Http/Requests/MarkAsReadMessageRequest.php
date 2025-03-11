<?php

namespace App\Http\Requests;

use App\Enums\StatusEnum;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class MarkAsReadMessageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $receiver = $this->user();

        if (!$receiver) {
            return false; // Ensure there is a logged-in user
        }

        $message = $this->route('message');
        
        if (!$message || $receiver->id == $message->receiver_id) {
            return false; // Ensure receiver exists, and can update
        }

        if ($receiver->getRawOriginal('status') !== StatusEnum::ACTIVE->value) {
            return false; // Ensure receiver is active
        }
        
        return true;
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
        $receiver = $this->user();

        if (!$receiver) {
            abort(response()->json([
                'message' => 'Unauthenticated.',
            ], 403));
        }

        if ($receiver->getRawOriginal('status') !== StatusEnum::ACTIVE->value) {
            abort(response()->json([
                'message' => 'The account is no longer available to read messages.',
            ], 403));
        }

        abort(response()->json([
            'message' => 'You are not authorized to update this message.',
        ], 403));
    }
}
