<?php
declare(strict_types=1);

namespace Minds\Core\Chat\Services;

use Minds\Core\Chat\Repositories\MessageRepository;
use Minds\Core\Chat\Repositories\RoomRepository;
use Minds\Core\Di\Di;
use Minds\Core\Di\ImmutableException;
use Minds\Core\Di\Provider;
use Minds\Core\Subscriptions\Relational\Repository as SubscriptionsRepository;

class ServicesProvider extends Provider
{
    /**
     * @return void
     * @throws ImmutableException
     */
    public function register(): void
    {
        $this->di->bind(
            RoomService::class,
            fn (Di $di): RoomService => new RoomService(
                roomRepository: $di->get(RoomRepository::class),
                subscriptionsRepository: $di->get(SubscriptionsRepository::class),
                entitiesBuilder: $di->get('EntitiesBuilder'),
                blockManager: $di->get('Security\Block\Manager')
            )
        );

        $this->di->bind(
            MessageService::class,
            fn (Di $di): MessageService => new MessageService(
                messageRepository: $di->get(MessageRepository::class),
                roomRepository: $di->get(RoomRepository::class),
                entitiesBuilder: $di->get('EntitiesBuilder')
            )
        );
    }
}