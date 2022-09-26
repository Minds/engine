<?php

declare(strict_types=1);

namespace Minds\Core\Supermind\Events;

use Minds\Core\Di\Di;
use Minds\Core\Events\Event;
use Minds\Core\Events\EventsDispatcher;
use Minds\Core\Supermind\Models\SupermindRequest;
use Minds\Entities\User;

class Events
{
    public function __construct(
        private ?EventsDispatcher $eventsDispatcher = null
    ) {
        $this->eventsDispatcher ??= Di::_()->get('EventsDispatcher');
    }

    public function register(): void
    {
        $this->supermindACLEvents();
    }

    private function supermindACLEvents(): void
    {
        $this->eventsDispatcher->register('acl:read', 'supermind', function (Event $event) {
            $params = $event->getParameters();

            /**
             * @var SupermindRequest $supermindRequest
             */
            $supermindRequest = $params['entity'];

            /**
             * @var User $user
             */
            $user = $params['user'];

            if (
                $supermindRequest->getSenderGuid() === $user->getGuid() ||
                $supermindRequest->getReceiverGuid() === $user->getGuid()
            ) {
                $event->setResponse(true);
            }
        });

        $this->eventsDispatcher->register('acl:write', 'supermind', function (Event $event) {
            $params = $event->getParameters();

            /**
             * @var SupermindRequest $supermindRequest
             */
            $supermindRequest = $params['entity'];

            /**
             * @var User $user
             */
            $user = $params['user'];

            $additionalData = $params['additionalData'] ?? [];

            if (isset($additionalData['isReply']) && $additionalData['isReply']) {
                if ($supermindRequest->getReceiverGuid() === $user->getGuid()) {
                    $event->setResponse(true);
                }
            } elseif (
                $supermindRequest->getSenderGuid() === $user->getGuid() ||
                $supermindRequest->getReceiverGuid() === $user->getGuid()
            ) {
                $event->setResponse(true);
            }
        });
    }
}
