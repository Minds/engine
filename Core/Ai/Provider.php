<?php
declare(strict_types=1);

namespace Minds\Core\Ai;

use Minds\Core\Ai\Ollama\OllamaClient;
use Minds\Core\Ai\Services\ChatProcessorService;
use Minds\Core\Ai\Services\CommentProcessorService;
use Minds\Core\Chat\Services\ChatImageStorageService;
use Minds\Core\Chat\Services\MessageService;
use Minds\Core\Chat\Services\RoomService;
use Minds\Core\Comments\Manager as CommentManager;
use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Core\Di\ImmutableException;
use Minds\Core\Di\Provider as DiProvider;
use Minds\Core\Entities\TaggedUsersService;
use Minds\Core\EntitiesBuilder;

class Provider extends DiProvider
{
    /**
     * @return void
     * @throws ImmutableException
     */
    public function register(): void
    {
        $this->di->bind(
            ChatProcessorService::class,
            fn (Di $di): ChatProcessorService => new ChatProcessorService(
                ollamaClient: $di->get(OllamaClient::class),
                chatMessageService: $di->get(MessageService::class),
                chatRoomService: $di->get(RoomService::class),
                chatImageStorageService: $di->get(ChatImageStorageService::class),
                config: $di->get(Config::class),
                entitiesBuilder: $di->get(EntitiesBuilder::class),
            )
        );

        $this->di->bind(
            CommentProcessorService::class,
            fn (Di $di): CommentProcessorService => new CommentProcessorService(
                ollamaClient: $di->get(OllamaClient::class),
                commentsManager: $di->get('Comments\Manager'),
                config: $di->get(Config::class),
                entitiesBuilder: $di->get(EntitiesBuilder::class),
                taggedUsersService: $di->get(TaggedUsersService::class),
                logger: $di->get('Logger'),
                acl: $di->get('Security\ACL'),
            )
        );

        $this->di->bind(
            OllamaClient::class,
            fn (Di $di): OllamaClient => new OllamaClient(
                httpClient: $di->get(\GuzzleHttp\Client::class),
                config: $di->get(Config::class),
            )
        );
    }
}
