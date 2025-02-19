<?php

namespace App\Services;

use App\Http\Resources\ConversationResource;
use App\Http\Resources\MessageResource;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Notifications\NewMessageNotification;
use App\Services\Interfaces\IMessageService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * Class MessageService
 *
 * Manages message-related operations such as sending messages, retrieving conversations, and fetching messages.
 */
class MessageService implements IMessageService
{
    /**
     * Send a new message and ensure a conversation exists.
     *
     * If a conversation between the sender and receiver does not exist, it is created.
     * The message is then stored within the conversation.
     *
     * @param  array  $validated  The validated data for creating the message.
     * @param  User  $sender  The user sending the message.
     * @return Message|null  Returns the created message instance or null if creation fails.
     *
     * @throws Exception  If the message creation fails.
     */
    public function send(array $validated, User $sender): ?Message
    {
        try {
            return DB::transaction(function () use ($validated, $sender) {
                $validated['business_id'] = $sender->ownerBusinessType?->id ?: $sender->businesses()->first()?->id;
                $validated['sender_id'] = $sender->id;

                $receiver = User::find($validated['receiver_id']);

                // Find existing conversation between these users
                $conversation = Conversation::where(
                    fn($query) => $query->where(
                        fn($q) => $q->where('sender_id', $sender->id)->where('receiver_id', $validated['receiver_id'])
                    )->orWhere(
                        fn($q) => $q->where('receiver_id', $sender->id)->where('sender_id', $validated['receiver_id'])
                    )
                )->first();

                // Create conversation if it doesn't exist
                if (!$conversation) {
                    $conversation = Conversation::create($validated);
                }

                // Store the new message within the conversation
                $message = $conversation->messages()->create($validated);

                if ($message) {
                    $receiver->notify(new NewMessageNotification('You have received a new message.'));
                    return $message;
                }

                return null;
            });
        } catch (Exception $e) {
            throw new Exception('Failed to send message: ' . $e->getMessage());
        }
    }

    /**
     * Retrieve a paginated list of the latest messages per conversation for the authenticated user.
     *
     * This query groups messages by the sender and receiver to get the latest message in each conversation.
     *
     * @param  Request  $request  The request instance containing pagination parameters.
     * @param  User  $user  The authenticated user.
     * @return LengthAwarePaginator  Returns a paginated list of the latest messages in conversations.
     */
    public function messages(Request $request, User $user): LengthAwarePaginator
    {
        $subquery = Message::selectRaw('MAX(id) as id')
            ->where('sender_id', $user->id)
            ->orWhere('receiver_id', $user->id)
            ->groupByRaw('LEAST(sender_id, receiver_id), GREATEST(sender_id, receiver_id)');

        return Message::whereIn('id', $subquery)
            ->latest('id')
            ->paginate($request->get('perPage', 10))
            ->withQueryString()
            ->through(fn(Message $message) => new MessageResource($message));
    }

    /**
     * Retrieve a paginated list of conversations for the authenticated user, sorted by the latest message.
     *
     * This method ensures conversations are sorted based on the most recent message.
     *
     * @param  Request  $request  The request instance containing pagination parameters.
     * @param  User  $user  The authenticated user.
     * @return LengthAwarePaginator  Returns a paginated list of conversations ordered by the latest message.
     */
    public function conversations(Request $request, User $user): LengthAwarePaginator
    {
        return Conversation::select('conversations.*')
            ->leftJoinSub(
                DB::table('messages')
                    ->selectRaw('MAX(id) as latest_message_id, conversation_id')
                    ->groupBy('conversation_id'),
                'latest_messages',
                'conversations.id',
                '=',
                'latest_messages.conversation_id'
            )
            ->where('sender_id', $user->id)
            ->orWhere('receiver_id', $user->id)
            ->orderByDesc('latest_messages.latest_message_id') // Sort conversations by the latest message
            ->paginate($request->get('perPage', 10))
            ->withQueryString()
            ->through(fn(Conversation $conversation) => new ConversationResource($conversation));
    }

    /**
     * Retrieve all messages within a specific conversation.
     *
     * This method paginates messages within a conversation and formats them into a resource.
     *
     * @param  Request  $request  The request instance containing pagination parameters.
     * @param  Conversation  $conversation  The conversation whose messages are being retrieved.
     * @return LengthAwarePaginator  Returns a paginated list of messages in the conversation.
     */
    public function conversation(Request $request, Conversation $conversation): LengthAwarePaginator
    {
        return $conversation->messages()
            ->paginate($request->get('perPage', 30))
            ->withQueryString()
            ->through(fn(Message $message) => new MessageResource($message));
    }
}
