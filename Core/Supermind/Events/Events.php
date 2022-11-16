<?php

declare(strict_types=1);

namespace Minds\Core\Supermind\Events;

use Minds\Core\Di\Di;
use Minds\Core\Events\Event;
use Minds\Core\Events\EventsDispatcher;
use Minds\Core\Supermind\Manager;
use Minds\Core\Supermind\Models\SupermindRequest;
use Minds\Entities\User;
use Minds\Core\EntitiesBuilder;

class Events
{
    public function __construct(
        private ?EventsDispatcher $eventsDispatcher = null,
        private ?Manager $supermindManager = null,
        private ?EntitiesBuilder $entitiesBuilder = null
    ) {
        $this->eventsDispatcher ??= Di::_()->get('EventsDispatcher');
    }

    public function register(): void
    {
        $this->supermindACLEvents();
        $this->supermindExportEvents();
    }

    private function supermindACLEvents(): void
    {
        $this->eventsDispatcher->register('acl:read', 'supermind', function (Event $event) {
            if (php_sapi_name() === "cli") {
                $event->setResponse(true);
                return;
            }

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
            if (php_sapi_name() === "cli") {
                $event->setResponse(true);
                return;
            }

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

    private function supermindExportEvents(): void
    {
        $this->eventsDispatcher->register('export:extender', 'activity', function ($event) {
            if (!$this->supermindManager) {
                $this->supermindManager = Di::_()->get('Supermind\Manager');
            }

            $params = $event->getParameters();
            $activity = $params['entity'];
            $export = $event->response() ?: [];

            $supermindRequest = $activity->getSupermind();
            if (!$supermindRequest) {
                return;
            }

            $isReply = $supermindRequest['is_reply'];
            if ($isReply) {
                return;
            }

            $requestGuid = $supermindRequest['request_guid'];

            $request = $this->supermindManager->getRequest($requestGuid);

            $receiverGuid = $request->getReceiverGuid();

            if (!$this->entitiesBuilder) {
                $this->entitiesBuilder = Di::_()->get('EntitiesBuilder');
            }

            $receiverUser = $this->entitiesBuilder->single($receiverGuid);
            if (!$receiverUser instanceof User) {
                return;
            }

            $replyGuid = $request->getReplyActivityGuid();

            $export['supermind'] = $supermindRequest;
            $export['supermind']['receiver_user'] = $receiverUser->export();
            $export['supermind']['reply_guid'] = $replyGuid;

            $event->setResponse($export);
        });
    }
}
