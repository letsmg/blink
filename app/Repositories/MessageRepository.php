<?php

namespace App\Repositories;

use App\Models\Message;

class MessageRepository extends BaseRepository
{
    public function __construct(Message $message)
    {
        parent::__construct($message);
    }

    /**
     * Count unread messages for a specific recipient.
     * Used for the dynamic notification badge in the Staff menu.
     */
    public function countUnreadByRecipient(int $recipientId): int
    {
        return $this->model
            ->where('recipient_id', $recipientId)
            ->where('is_read', false)
            ->count();
    }

    public function findByRecipient(int $recipientId, int $perPage = 15)
    {
        return $this->model
            ->where('recipient_id', $recipientId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }
}
