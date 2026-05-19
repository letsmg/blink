<?php

namespace App\Services;

use App\Models\Message;
use App\Models\User;
use App\Repositories\MessageRepository;

class MessageService
{
    public function __construct(
        private readonly MessageRepository $messageRepository,
    ) {}

    /**
     * Send a message to a specific recipient.
     */
    public function send(User $sender, int $recipientId, string $subject, string $body): Message
    {
        return $this->messageRepository->create([
            'sender_id' => $sender->id,
            'recipient_id' => $recipientId,
            'subject' => $subject,
            'body' => $body,
        ]);
    }

    /**
     * Get unread messages count for a user.
     * Used for the dynamic notification badge.
     */
    public function getUnreadCount(int $userId): int
    {
        return $this->messageRepository->countUnreadByRecipient($userId);
    }

    /**
     * Get paginated messages for a recipient.
     */
    public function getMessagesForRecipient(int $recipientId, int $perPage = 15)
    {
        return $this->messageRepository->findByRecipient($recipientId, $perPage);
    }

    /**
     * Mark a message as read.
     */
    public function markAsRead(Message $message): void
    {
        $message->markAsRead();
    }
}
