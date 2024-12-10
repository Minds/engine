<?php
declare(strict_types=1);

namespace Minds\Core\Chat\Services;

use Minds\Core\Chat\Delegates\AnalyticsDelegate;
use Minds\Core\Chat\Repositories\MessageRepository;
use Minds\Core\Chat\Repositories\ReceiptRepository;
use Minds\Core\Chat\Repositories\RoomRepository;
use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Core\Di\ImmutableException;
use Minds\Core\Di\Provider;
use Minds\Core\Security\Rbac\Services\RolesService;
use Minds\Core\EventStreams\Topics\ChatNotificationsTopic;
use Minds\Core\Groups\V2\Membership\Manager as GroupMembershipManager;
use Minds\Core\Media\BlurHash;
use Minds\Core\Media\Services\OciS3Client;
use Minds\Core\Sockets\Events as SocketEvents;
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
                blockManager: $di->get('Security\Block\Manager'),
                rolesService: $di->get(RolesService::class),
                groupMembershipManager: $di->get(GroupMembershipManager::class),
                analyticsDelegate: $di->get(AnalyticsDelegate::class),
                logger: $di->get('Logger')
            )
        );

        $this->di->bind(
            MessageService::class,
            fn (Di $di): MessageService => new MessageService(
                messageRepository: $di->get(MessageRepository::class),
                roomRepository: $di->get(RoomRepository::class),
                imageStorageService: $di->get(ChatImageStorageService::class),
                imageProcessorService: $di->get(ChatImageProcessorService::class),
                receiptService: $di->get(ReceiptService::class),
                entitiesBuilder: $di->get('EntitiesBuilder'),
                socketEvents: new SocketEvents(),
                chatNotificationsTopic: $di->get(ChatNotificationsTopic::class),
                chatRichEmbedService: Di::_()->get(RichEmbedService::class),
                analyticsDelegate: $di->get(AnalyticsDelegate::class),
                acl: $di->get('Security\ACL'),
                logger: $di->get('Logger')
            )
        );

        $this->di->bind(
            ReceiptService::class,
            fn (Di $di): ReceiptService => new ReceiptService(
                repository: $di->get(ReceiptRepository::class),
            )
        );

        $this->di->bind(
            RichEmbedService::class,
            fn (Di $di): RichEmbedService => new RichEmbedService(
                metascraperService: $di->get('Metascraper\Service'),
                logger: $di->get('Logger')
            )
        );

        $this->di->bind(
            ChatImageProcessorService::class,
            fn (Di $di): ChatImageProcessorService => new ChatImageProcessorService(
                imageStorageService: $di->get(ChatImageStorageService::class),
                blurHash: new BlurHash(),
                logger: $di->get('Logger')
            )
        );

        $this->di->bind(
            ChatImageStorageService::class,
            fn (Di $di): ChatImageStorageService => new ChatImageStorageService(
                config: $di->get(Config::class),
                ociS3: $di->get(OciS3Client::class),
            )
        );
    }
}
