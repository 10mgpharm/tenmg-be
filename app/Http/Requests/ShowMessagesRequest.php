<?php

namespace App\Http\Requests;

use App\Enums\StatusEnum;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ShowMessagesRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $sender = $this->user();
        $conversation = $this->route('conversation');

        if (!$sender || !$conversation) {
            return false; // Ensure there is a logged-in user and a conversation
        }

        if($conversation->sender_id !== $sender->id || $conversation->receiver_id !== $sender->id){
            return false;
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

        if (!$sender) {
            abort(response()->json([
                'message' => 'Unauthenticated.',
            ], 403));
        }

        abort(response()->json([
            'message' => 'You are not authorized to view these messages.',
        ], 403));
    }
}
