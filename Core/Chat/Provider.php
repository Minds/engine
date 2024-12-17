<?php
declare(strict_types=1);

namespace Minds\Core\Chat;

use Minds\Core\Chat\Controllers\ChatController;
use Minds\Core\Chat\Controllers\ChatImagePsrController;
use Minds\Core\Chat\Services\ChatImageStorageService;
use Minds\Core\Chat\Services\MessageService;
use Minds\Core\Chat\Services\ReceiptService;
use Minds\Core\Chat\Services\RoomService;
use Minds\Core\Di\Di;
use Minds\Core\Di\ImmutableException;
use Minds\Core\Di\Provider as DiProvider;
use Minds\Core\Security\ACL;

class Provider extends DiProvider
{
    /**
     * @return void
     * @throws ImmutableException
     */
    public function register(): void
    {
        $this->di->bind(
            ChatController::class,
            fn (Di $di): ChatController => new ChatController(
                roomService: $di->get(RoomService::class),
                messageService: $di->get(MessageService::class),
                receiptService: $di->get(ReceiptService::class),
            )
        );

        $this->di->bind(
            ChatImagePsrController::class,
            fn (Di $di): ChatImagePsrController => new ChatImagePsrController(
                imageStorageService: $di->get(ChatImageStorageService::class),
                messageService: $di->get(MessageService::class),
                logger: $di->get('Logger'),
            )
        );

        (new Services\ServicesProvider())->register();
        (new Repositories\RepositoriesProvider())->register();
        (new Notifications\NotificationsProvider())->register();
        (new Delegates\DelegatesProvider())->register();
    }
}
