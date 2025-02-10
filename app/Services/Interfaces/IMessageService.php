<?php

namespace App\Services\Interfaces;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Interface IMessageService
 *
 * Defines the contract for handling message-related operations.
 */
interface IMessageService
{
    /**
     * Send a new message and ensure a conversation exists.
     *
     * @param  array  $data  The validated data for creating the message.
     * @param  User  $sender  The sender sending the message.
     * @return Message|null  Returns the created message model or null on failure.
     */
    public function send(array $data, User $sender): ?Message;

    /**
     * Retrieve a paginated list of the latest messages per conversation for the authenticated user.
     *
     * @param  Request  $request  The request instance containing pagination parameters.
     * @param  User  $user  The authenticated user.
     * @return LengthAwarePaginator  Returns a paginated list of the latest messages in conversations.
     */
    public function messages(Request $request, User $user): LengthAwarePaginator;

    /**
     * Retrieve a paginated list of conversations for the authenticated user, sorted by the latest message.
     *
     * @param  Request  $request  The request instance containing pagination parameters.
     * @param  User  $user  The authenticated user.
     * @return LengthAwarePaginator  Returns a paginated list of conversations ordered by the latest message.
     */
    public function conversations(Request $request, User $user): LengthAwarePaginator;

    /**
     * Retrieve all messages within a specific conversation.
     *
     * @param  Request  $request  The request instance containing pagination parameters.
     * @param  Conversation  $conversation  The conversation whose messages are being retrieved.
     * @return LengthAwarePaginator  Returns a paginated list of messages in the conversation.
     */
    public function conversation(Request $request, Conversation $conversation): LengthAwarePaginator;
}
