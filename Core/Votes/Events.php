<?php

/**
 * Votes Events
 *
 * @author emi
 */

namespace Minds\Core\Votes;

use Minds\Core;
use Minds\Core\Di\Di;
use Minds\Core\Events\Dispatcher;
use Minds\Core\Events\Event;
use Minds\Core\EventStreams\ActionEvent;
use Minds\Core\EventStreams\Topics\ActionEventsTopic;
use Minds\Core\Experiments\Manager as ExperimentsManager;
use Minds\Core\Wire\Paywall\PaywallEntityInterface;
use Minds\Entities;
use Minds\Entities\Activity;
use Minds\Helpers;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\ServerRequestFactory;

class Events
{
    public function __construct(
        private ?ExperimentsManager $experimentsManager = null
    ) {
    }

    public function register()
    {
        // Notification stream event
        Dispatcher::register('vote', 'all', function (Event $event) {
            $params = $event->getParameters();
            $direction = $event->getNamespace();

            $vote = $params['vote'];
            $entity = $vote->getEntity();
            $actor = $vote->getActor();

            // If this is an activity post, then we will make the action on the image or video
            if ($entity instanceof Activity && $entityGuid = $entity->getEntityGuid()) {
                $entity = Di::_()->get('EntitiesBuilder')->single($entityGuid);
                if (!$entity) {
                    return; // Nothing we can do here...
                }
            }

            $actionEvent = new ActionEvent();
            $actionEvent
                ->setAction(
                    $direction === 'up' ? ActionEvent::ACTION_VOTE_UP : ActionEvent::ACTION_VOTE_DOWN
                )
                ->setEntity($entity)
                ->setUser($actor);

            $actionEventTopic = new ActionEventsTopic();
            $actionEventTopic->send($actionEvent);
        });

        // Notification events

        Dispatcher::register('vote', 'all', function (Event $event) {
            $params = $event->getParameters();
            $direction = $event->getNamespace();

            $vote = $params['vote'];
            $entity = $vote->getEntity();
            $actor = $vote->getActor();

            if ($entity->owner_guid == $actor->guid) {
                return;
            }

            $params = [
                'title' => $entity->title ?: $entity->message,
            ];

            if ($entity->type === 'comment') {
                $params['focusedCommentUrn'] = $entity->getUrn();
            }

            Dispatcher::trigger('notification', 'thumbs', [
                'to' => [$entity->owner_guid],
                'notification_view' => $direction == 'up' ? 'like' : 'downvote',
                'entity' => $entity,
                'params' => $params,
            ]);
        });

        // Analytics events

        Dispatcher::register('vote', 'all', function (Event $event) {
            $request = $this->retrieveServerRequest();
            $experimentsManager = $this->getExperimentsManager()->setUser($request->getAttribute('_user'));

            $params = $event->getParameters();
            $direction = $event->getNamespace();

            $vote = $params['vote'];
            $proofOfWork = $params['isFriendlyCaptchaPuzzleValid'] ?? false;
            $entity = $vote->getEntity();
            $actor = $vote->getActor();

            $container_guid = $entity->type === 'comment' ? $entity->parent->container_guid : $entity->container_guid;

            if ($entity->type == 'activity' && $entity->custom_type) {
                $subtype = '';
                $guid = '';
                switch ($entity->custom_type) {
                    case 'video':
                        $subtype = 'video';
                        $guid = $entity->custom_data['guid'];
                        break;
                    case 'batch':
                        $subtype = 'image';
                        $guid = $entity->entity_guid;
                        break;
                }

                $event = new Core\Analytics\Metrics\Event();
                $event->setType('action')
                    ->setProduct('platform')
                    ->setUserGuid((string) $actor->guid)
                    ->setUserPhoneNumberHash($actor->getPhoneNumberHash())
                    ->setEntityGuid($guid)
                    ->setEntityContainerGuid((string) $container_guid)
                    ->setEntityAccessId($entity->access_id)
                    ->setEntityType('object')
                    ->setEntitySubtype($subtype)
                    ->setEntityOwnerGuid((string) $entity->owner_guid)
                    ->setAction("vote:{$direction}");

                if ($experimentsManager->isOn("minds-3119-captcha-for-engagement")) {
                    $event->setProofOfWork($proofOfWork);
                }

                if ($entity instanceof PaywallEntityInterface) {
                    $wireThreshold = $entity->getWireThreshold();
                    if ($wireThreshold['support_tier'] ?? null) {
                        $event->setSupportTierUrn($wireThreshold['support_tier']['urn']);
                    }
                }

                $event->push();

                // Do not record to activity too
                // Core/Search/MetricsSync handles this
                return;
            }

            $event = new Core\Analytics\Metrics\Event();
            $event->setType('action')
                ->setProduct('platform')
                ->setUserGuid((string) $actor->guid)
                ->setUserPhoneNumberHash($actor->getPhoneNumberHash())
                ->setEntityGuid((string) $entity->guid)
                ->setEntityContainerGuid((string) $container_guid)
                ->setEntityAccessId($entity->access_id)
                ->setEntityType($entity->type)
                ->setEntitySubtype((string) $entity->subtype)
                ->setEntityOwnerGuid((string) $entity->owner_guid)
                ->setAction("vote:{$direction}");

            if ($entity->type == 'activity' && $entity->remind_object) {
                $event->setIsRemind(true);
            }

            if ($entity instanceof PaywallEntityInterface) {
                $wireThreshold = $entity->getWireThreshold();
                if ($wireThreshold['support_tier'] ?? null) {
                    $event->setSupportTierUrn($wireThreshold['support_tier']['urn']);
                }
            }

            $event->push();
        });

        Dispatcher::register('vote:cancel', 'all', function (Event $event) {
            $params = $event->getParameters();
            $direction = $event->getNamespace();

            $vote = $params['vote'];
            $entity = $vote->getEntity();
            $actor = $vote->getActor();

            $container_guid = $entity->type === 'comment' ? $entity->parent->container_guid : $entity->container_guid;

            $event = new Core\Analytics\Metrics\Event();
            $event->setType('action')
                ->setProduct('platform')
                ->setUserGuid((string) $actor->guid)
                ->setUserPhoneNumberHash($actor->getPhoneNumberHash())
                ->setEntityGuid((string) $entity->guid)
                ->setEntityContainerGuid((string) $container_guid)
                ->setEntityAccessId($entity->access_id)
                ->setEntityType($entity->type)
                ->setEntitySubtype((string) $entity->subtype)
                ->setEntityOwnerGuid((string) $entity->owner_guid)
                ->setAction("vote:{$direction}:cancel");

            if ($entity instanceof PaywallEntityInterface) {
                $wireThreshold = $entity->getWireThreshold();
                if ($wireThreshold['support_tier'] ?? null) {
                    $event->setSupportTierUrn($wireThreshold['support_tier']['urn']);
                }
            }

            $event->push();
        });

        /**
         * Exports the counter column for the votes
         */
        Dispatcher::register('export:extender', 'all', function (Event $event) {
            $export = $event->response() ?: [];
            $params = $event->getParameters();
            $entity = $params['entity'];

            $guid = $entity->getGuid();

            switch (get_class($entity)) {
                case Entities\Activity::class:
                    // Is there an attachment?
                    if ($entity->entity_guid) {
                        $guid = $entity->entity_guid;

                        // The below isn't the most efficient, but it attempts to avoid duplicate votes
                        // when entity_guid (attachment) activity posts exist
                        if ($canonicalEntity = $entity->getEntity()) {
                            $entity = $canonicalEntity;
                        }
                    }
                    break;
                case Entities\User::class:
                    break;
            }

            $upCount = Helpers\Counters::get($guid, 'thumbs:up');
            $downCount = Helpers\Counters::get($guid, 'thumbs:down');


            $export['thumbs:up:count'] = $upCount;
            $export['thumbs:down:count'] = $downCount;

            // Make sure our export of voters is an array and not an object
            $export['thumbs:up:user_guids'] = $entity->{'thumbs:up:user_guids'} ? (array) array_values($entity->{'thumbs:up:user_guids'}) : [];
            $export['thumbs:down:user_guids'] = $entity->{'thumbs:down:user_guids'} ? (array) array_values($entity->{'thumbs:down:user_guids'}) : [];

            $event->setResponse($export);
        });
    }

    /**
     * Do not call this inside of constructor or register functions as it will cause a race condition with config
     * breaking snowplow events
     * @return ExperimentsManager
     */
    private function getExperimentsManager(): ExperimentsManager
    {
        return $this->experimentsManager ??= Di::_()->get("Experiments\Manager");
    }

    private function retrieveServerRequest(): ServerRequestInterface
    {
        return ServerRequestFactory::fromGlobals();
    }
}
