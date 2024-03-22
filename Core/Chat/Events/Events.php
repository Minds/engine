<?php
declare(strict_types=1);

namespace Minds\Core\Chat\Events;

use Minds\Core\Chat\Entities\ChatMessage;
use Minds\Core\Chat\Entities\ChatRoom;
use Minds\Core\Chat\Services\RoomService as ChatRoomService;
use Minds\Core\Events\Event;
use Minds\Core\Events\EventsDispatcher;
use Minds\Entities\User;

class Events
{
    public function __construct(
        private readonly EventsDispatcher $eventsDispatcher,
        private readonly ChatRoomService $chatRoomService
    ) {
    }

    public function register(): void
    {
        $this->eventsDispatcher->register(
            event: 'acl:read',
            namespace: 'chat',
            handler: function (Event $event): void {
                /**
                 * @var ChatMessage|ChatRoom $entity
                 * @var User $user
                 */
                ['user' => $user, 'entity' => $entity] = $event->getParameters();

                $event->setResponse(
                    $this->chatRoomService->isUserMemberOfRoom(
                        user: $user,
                        roomGuid: $entity->roomGuid
                    )
                );
            }
        );
    }
}
