<?php
declare(strict_types=1);

namespace Minds\Core\Chat\Events;

use Minds\Core\Chat\Entities\ChatMessage;
use Minds\Core\Chat\Entities\ChatRoom;
use Minds\Core\Chat\Services\RoomService as ChatRoomService;
use Minds\Core\Di\Di;
use Minds\Core\Events\Event;
use Minds\Core\Events\EventsDispatcher;
use Minds\Entities\User;

class Events
{
    public function __construct(
        private readonly EventsDispatcher $eventsDispatcher,
    ) {
    }

    public function register(): void
    {
        $chatRoomService = $this->getChatRoomService();
        $this->eventsDispatcher->register(
            event: 'acl:read',
            namespace: 'chat',
            handler: function (Event $event) use ($chatRoomService): void {
                /**
                 * @var ChatMessage|ChatRoom $entity
                 * @var User $user
                 */
                ['user' => $user, 'entity' => $entity] = $event->getParameters();

                $event->setResponse(
                    $chatRoomService->isUserMemberOfRoom(
                        user: $user,
                        roomGuid: $entity->roomGuid
                    )
                );
            }
        );
    }

    private function getChatRoomService(): ChatRoomService
    {
        return Di::_()->get(ChatRoomService::class);
    }
}
