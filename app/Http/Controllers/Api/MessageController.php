<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SendMessageRequest;
use App\Models\Message;
use App\Services\MessageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function __construct(
        private readonly MessageService $messageService,
    ) {}

    /**
     * Send a message to a specific recipient.
     */
    public function send(SendMessageRequest $request): JsonResponse
    {
        $message = $this->messageService->send(
            $request->user(),
            $request->validated()['recipient_id'],
            $request->validated()['subject'],
            $request->validated()['body'],
        );

        return response()->json([
            'message' => 'Mensagem enviada com sucesso!',
            'data' => $message,
        ], 201);
    }

    /**
     * Get paginated messages for the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $messages = $this->messageService->getMessagesForRecipient(
            $request->user()->id,
            $request->get('per_page', 15),
        );

        return response()->json(['data' => $messages]);
    }

    /**
     * Get unread messages count for the notification badge.
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $count = $this->messageService->getUnreadCount($request->user()->id);

        return response()->json(['unread_count' => $count]);
    }

    /**
     * Mark a message as read.
     */
    public function markAsRead(Message $message, Request $request): JsonResponse
    {
        // Ensure the message belongs to the authenticated user
        if ($message->recipient_id !== $request->user()->id) {
            return response()->json(['message' => 'Não autorizado.'], 403);
        }

        $this->messageService->markAsRead($message);

        return response()->json(['message' => 'Mensagem marcada como lida.']);
    }
}
