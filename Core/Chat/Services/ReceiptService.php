<?php

namespace Minds\Core\Chat\Services;

use Minds\Core\Chat\Entities\ChatMessage;
use Minds\Core\Chat\Repositories\ReceiptRepository;
use Minds\Entities\User;

class ReceiptService
{
    public function __construct(
        private ReceiptRepository $repository
    ) {
    }

    /**
     * Updates the the read receipt on the database
     */
    public function updateReceipt(ChatMessage $message, User $member)
    {
        return $this->repository->updateReceipt(
            roomGuid: $message->roomGuid,
            messageGuid: $message->guid,
            memberGuid: (int) $member->getGuid(),
        );
    }

    /**
     * Returns a count of all the unread messages a user has
     */
    public function getAllUnreadMessagesCount(User $user)
    {
        return $this->repository->getAllUnreadMessagesCount(
            memberGuid: (int) $user->getGuid(),
        );
    }
}
