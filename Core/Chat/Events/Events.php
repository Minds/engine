<?php
declare(strict_types=1);

namespace Minds\Core\Chat\Events;

use Minds\Core\Chat\Entities\ChatMessage;
use Minds\Core\Chat\Entities\ChatRoom;
use Minds\Core\Chat\Services\RoomService as ChatRoomService;
use Minds\Core\Di\Di;
use Minds\Core\Events\Event;
use Minds\Core\Events\EventsDispatcher;
use Minds\Core\Security\ACL;
use Minds\Entities\User;

class Events
{
    public function __construct(
        private readonly EventsDispatcher $eventsDispatcher,
        private readonly ACL $acl
    ) {
    }

    public function register(): void
    {
        $this->eventsDispatcher->register(
            event: 'acl:read',
            namespace: 'chat',
            handler: function (Event $event): void {
                $chatRoomService = $this->getChatRoomService();
                /**
                 * @var ChatMessage|ChatRoom $entity
                 * @var User $user
                 */
                ['user' => $user, 'entity' => $entity] = $event->getParameters();

                if (!$this->acl->read(entity: $entity, user: $user)) {
                    $event->setResponse(false);
                    return;
                }

                $event->setResponse(
                    $chatRoomService->isUserMemberOfRoom(
                        user: $user,
                        roomGuid: $entity instanceof ChatMessage ?
                            $entity->roomGuid :
                            $entity->guid
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
