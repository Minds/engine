<?php
declare(strict_types=1);

namespace Minds\Core\Entities\Delegates;

use Minds\Common\Urn;
use Minds\Core\Chat\Entities\ChatMessage;
use Minds\Core\Chat\Exceptions\ChatMessageNotFoundException;
use Minds\Core\Chat\Services\MessageService as ChatMessageService;
use Minds\Core\Di\Di;
use Minds\Exceptions\ServerErrorException;

class ChatMessageResolverDelegate implements ResolverDelegate
{
    public function __construct(
        private ?ChatMessageService $chatMessageService = null
    ) {
    }

    public function shouldResolve(Urn $urn): bool
    {
        return $urn->getNid() === ChatMessage::URN_METHOD;
    }

    /**
     * @param array $urns
     * @param array $opts
     * @return array|null
     * @throws ServerErrorException
     */
    public function resolve(array $urns, array $opts = []): ?array
    {
        $this->initialiseChatMessageService();
        $entities = [];
        foreach ($urns as $urn) {
            try {
                [$roomGuid, $messageGuid] = explode('_', $urn->getNss());
                if (!$roomGuid || !$messageGuid) {
                    // TODO: log invalid urn
                    continue;
                }

                $entities[] = $this->chatMessageService->getMessage(
                    roomGuid: (int) $roomGuid,
                    messageGuid: (int) $messageGuid,
                    skipPermissionCheck: php_sapi_name() === 'cli'
                );
            } catch (ChatMessageNotFoundException $e) {
                // TODO: log not found
                continue;
            }
        }

        return $entities;
    }

    public function map($urn, $entity): mixed
    {
        return $entity;
    }

    /**
     * @param ChatMessage|null $entity
     * @return string|null
     */
    public function asUrn($entity): ?string
    {
        if (!($entity instanceof ChatMessage)) {
            return null;
        }
        return $entity->getUrn();
    }

    public function initialiseChatMessageService(): void
    {
        $this->chatMessageService ??= Di::_()->get(ChatMessageService::class);
    }
}
