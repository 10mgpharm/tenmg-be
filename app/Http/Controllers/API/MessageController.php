<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\MarkAsReadMessageRequest;
use App\Http\Requests\SendMessageRequest;
use App\Http\Resources\MessageResource;
use App\Http\Resources\MessageUserResource;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Services\MessageService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class MessageController extends Controller
{
    /**
     * MessageController constructor.
     *
     * @param  MessageService  $messageService  The message service instance.
     */
    public function __construct(private MessageService $messageService) {}

    /**
     * Retrieve all conversations for the authenticated user.
     *
     * This method fetches a paginated list of conversations where the authenticated
     * user is either the sender or receiver, ordered by the latest message.
     *
     * @param  Request  $request  The request instance containing pagination and filter parameters.
     * @return JsonResponse  Returns a JSON response with the list of conversations.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $conversations = $this->messageService->conversations($request, $user);

        return $this->returnJsonResponse(
            message: 'Conversations successfully fetched.',
            data: $conversations
        );
    }

    /**
     * Send a new message from the authenticated user.
     *
     * Validates the request data, creates a new message within an existing or new conversation,
     * and returns the created message.
     *
     * @param  SendMessageRequest  $request  The validated request instance containing message details.
     * @return JsonResponse  Returns a JSON response with the sent message details.
     */
    public function store(SendMessageRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $sender = $request->user();

        $message = $this->messageService->send($validated, $sender);

        if (! $message) {
            return $this->returnJsonResponse(
                message: 'Oops, can\'t send message at the moment. Please try again later.'
            );
        }

        return $this->returnJsonResponse(
            message: 'Message sent successfully.',
            data: new MessageResource($message)
        );
    }

    /**
     * Receiver Mark message has read.
     *
     * Validates the request data, mark the message as read,
     * and returns the updated message.
     *
     * @param  MarkAsReadMessageRequest $request  The validated request instance containing message details.
     * @return JsonResponse  Returns a JSON response with the sent message details.
     */
    public function markAsRead(MarkAsReadMessageRequest $request, Message $message): JsonResponse
    {

        $message = $this->messageService->markAsRead($message);

        if (! $message) {
            return $this->returnJsonResponse(
                message: 'Oops, can\'t update message at the moment. Please try again later.'
            );
        }

        return $this->returnJsonResponse(
            message: 'Message updated successfully.',
            data: new MessageResource($message)
        );
    }

    /**
     * Retrieve messages in a specific conversation.
     *
     * This method fetches all messages exchanged within a given conversation,
     * ensuring only authorized users can access the conversation.
     *
     * @param  Request  $request  The request instance containing pagination and filter parameters.
     * @param  Conversation  $message  The conversation instance.
     * @return JsonResponse  Returns a JSON response with the messages in the conversation.
     */
    public function show(Request $request, Conversation $message): JsonResponse
    {
        $messages = $this->messageService->conversation($request, $message);

        return $this->returnJsonResponse(
            message: 'Messages successfully fetched.',
            data: $messages
        );
    }

    public function startConversation(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = User::query()
            ->where('id', '!=', $user->id)
            ->when(
                $request->input('search'),
                fn($q, $search) =>
                $q->where(
                    fn($q) =>
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                )
            );

        if ($user->hasRole('admin')) {
            // Admins: fetch all primary users of businesses, excluding pharmacies aka customer
            $query->whereHas('ownerBusinessType')
                ->whereDoesntHave('roles', fn($q) => $q->where('name', 'customer'));
        } else {
            // Non-admins: limit to users within their business or admins
            $query->where(
                fn($q) =>
                $q->withinBusiness()
                    ->orWhereHas('roles', fn($r) => $r->where('name', 'admin'))
            );
        }

        $users = $query->paginate($request->get('perPage', 30))
            ->withQueryString()
            ->through(fn(User $user) => new MessageUserResource($user));

        return $this->returnJsonResponse(
            message: 'Conversable users successfully fetched.',
            data: $users
        );
    }


    public function unreadCount(Request $request): JsonResponse
    {
        $user = $request->user();
        $count = $this->messageService->unreadCount($request, $user);

        return $this->returnJsonResponse(
            message: 'Unread message(s) successfully counted.',
            data: $count
        );
    }
}
