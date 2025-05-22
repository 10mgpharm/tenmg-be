<?php

namespace App\Http\Requests;

use App\Enums\StatusEnum;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class SendMessageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $sender = $this->user();

        if (!$sender || !$this->input('receiver_id')) {
            return false; // Ensure there is a logged-in user and a receiver
        }

        $receiver = User::find($this->input('receiver_id'));
        
        if (!$receiver || $sender->id == $receiver->id) {
            return false; // Ensure receiver exists and sender is not the receiver
        }
        
        if ($sender->hasRole('admin')) {
            return true; // Admins can always send messages
        }

        if ($receiver->getRawOriginal('status') !== StatusEnum::ACTIVE->value) {
            return false; // Ensure receiver is active
        }
        
        // Only allow messaging within the same business unless sender is an admin
        return ($sender->ownerBusinessType?->id ?? $sender->businesses()->first()?->id) ===
            ($receiver->ownerBusinessType?->id ?? $receiver->businesses()->first()?->id) ||
            $receiver->hasRole('admin'); // Allow messaging admins
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'message' => ['required', 'string'],
            'attachments' => ['sometimes', 'nullable', 'array'],
            'attachments.*' => ['sometimes', 'file', 'size:1024'],
            'receiver_id' => ['required', 'exists:users,id'],
        ];
    }

    /**
     * Custom response for failed authorization.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function failedAuthorization()
    {
        $sender = $this->user();
        $receiver = User::find($this->input('receiver_id'));

        if (!$sender) {
            abort(response()->json([
                'message' => 'Unauthenticated.',
            ], 403));
        }

        if (!$receiver) {
            abort( response()->json([
                'message' => 'The account is not available to receive messages.',
            ], 403));
        }

        if ($sender->id == $receiver->id) {
            abort( response()->json([
                'message' => 'You cannot send a message to yourself.',
            ], 403));
        }

        $send_business_id = $sender->ownerBusinessType?->id
            ?: $sender->businesses()->first()?->id;

        $receiver_business_id = $receiver->ownerBusinessType?->id
            ?: $receiver->businesses()->first()?->id;

        if ($receiver->getRawOriginal('status') !== StatusEnum::ACTIVE->value) {
            abort(response()->json([
                'message' => 'The account is no longer available to receive messages.',
            ], 403));
        }

        if ($send_business_id !== $receiver_business_id && !$receiver->hasRole('admin')) {
            abort(response()->json([
                'message' => 'You are not authorized to send messages outside your business, unless messaging an admin.',
            ], 403));
        }

        abort(response()->json([
            'message' => 'You are not authorized to send messages.',
        ], 403));
    }
}
