<?php

declare(strict_types=1);

namespace Minds\Core\Supermind\Events;

use Minds\Core\Di\Di;
use Minds\Core\Events\Event;
use Minds\Core\Events\EventsDispatcher;
use Minds\Core\Supermind\Manager as SupermindManager;
use Minds\Core\Supermind\Models\SupermindRequest;
use Minds\Entities\User;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Security\ACL;

class Events
{
    public function __construct(
        private ?EventsDispatcher $eventsDispatcher = null,
        private ?SupermindManager $supermindManager = null,
        private ?EntitiesBuilder $entitiesBuilder = null,
        private ?ACL $acl = null,
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
            $params = $event->getParameters();
            $activity = $params['entity'];
            $export = $event->response() ?: [];

            // Contains the request guid and other metadata we may need
            $supermindRequest = $activity->getSupermind();

            // If not a supermind, skip
            if (!$supermindRequest) {
                return;
            }

            // We dont care if this is a reply, so skip
            $isReply = $supermindRequest['is_reply'];
            if ($isReply) {
                return;
            }

            $requestGuid = $supermindRequest['request_guid'];

            // We need to bypass acl to get info about the supermind
            $ia = $this->getAcl()->setIgnore(true);

            // Fetch the supermind
            $request = $this->getSupermindManager()->getRequest($requestGuid);

            // Set ACL back to what it was
            $this->getAcl()->setIgnore($ia);

            $receiverGuid = $request->getReceiverGuid();

            // Build the receiving user
            $receiverUser = $this->getEntitiesBuilder()->single($receiverGuid);
            if (!$receiverUser instanceof User) {
                return;
            }

            $replyGuid = $request->getReplyActivityGuid();

            // Construct the export output
            $export['supermind'] = $supermindRequest;
            $export['supermind']['receiver_user'] = $receiverUser->export();
            $export['supermind']['reply_guid'] = $replyGuid;

            $event->setResponse($export);
        });
    }

    private function getEntitiesBuilder(): EntitiesBuilder
    {
        return $this->entitiesBuilder ??= Di::_()->get('EntitiesBuilder');
    }

    private function getAcl(): ACL
    {
        return $this->acl ??= Di::_()->get('Security\ACL');
    }

    private function getSupermindManager(): SupermindManager
    {
        return $this->supermindManager ??= Di::_()->get('Supermind\Manager');
    }
}
