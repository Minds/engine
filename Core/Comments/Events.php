<?php

/**
 * Minds Comments Events Listeners
 *
 * @author emi
 */

namespace Minds\Core\Comments;

use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Events\Dispatcher;
use Minds\Core\Events\Event;
use Minds\Entities\Factory as EntitiesFactory;
use Minds\Core\Sockets;
use Minds\Core\Session;
use Minds\Core\Security\ACL;
use Minds\Core\Security\SignedUri;
use Minds\Core\Wire\Paywall\PaywallEntityInterface;
use Minds\Entities\Image;
use Minds\Entities\Video;

class Events
{
    /** @var Dispatcher */
    protected $eventsDispatcher;

    /**
     * Events constructor.
     * @param Dispatcher $eventsDispatcher
     */
    public function __construct($eventsDispatcher = null)
    {
        $this->eventsDispatcher = $eventsDispatcher ?: Di::_()->get('EventsDispatcher');
    }

    public function register()
    {
        // Entity resolver

        $this->eventsDispatcher->register('entity:resolve', 'comment', function (Event $event) {
            $luid = $event->getParameters()['luid'];
            $manager = new Manager();
            $event->setResponse($manager->getByLuid($luid));
        });

        // Entity save

        $this->eventsDispatcher->register('entity:save', 'comment', function (Event $event) {
            $comment = $event->getParameters()['entity'];
            $manager = new Manager();
            $event->setResponse($manager->update($comment));
        });

        // Votes Module

        $this->eventsDispatcher->register('vote:action:has', 'comment', function (Event $event) {
            $votesManager = new Votes\Manager();
            $event->setResponse(
                $votesManager
                    ->setVote($event->getParameters()['vote'])
                    ->has()
            );
        });

        $this->eventsDispatcher->register('vote:action:cast', 'comment', function (Event $event) {
            $vote = $event->getParameters()['vote'];
            $comment = $vote->getEntity();

            (new Sockets\Events())
                ->setRoom("comments:{$comment->getEntityGuid()}:{$comment->getParentPath()}")
                ->emit(
                    'vote',
                    (string) $comment->getGuid(),
                    (string) Session::getLoggedInUser()->guid,
                    $vote->getDirection()
                );

            $votesManager = new Votes\Manager();
            $event->setResponse(
                $votesManager
                    ->setVote($event->getParameters()['vote'])
                    ->cast()
            );
        });

        $this->eventsDispatcher->register('vote:action:cancel', 'comment', function (Event $event) {
            $vote = $event->getParameters()['vote'];
            $comment = $vote->getEntity();

            (new Sockets\Events())
                ->setRoom("comments:{$comment->getEntityGuid()}:{$comment->getParentPath()}")
                ->emit(
                    'vote:cancel',
                    (string) $comment->getGuid(),
                    (string) Session::getLoggedInUser()->guid,
                    $vote->getDirection()
                );

            $votesManager = new Votes\Manager();
            $event->setResponse(
                $votesManager
                    ->setVote($event->getParameters()['vote'])
                    ->cancel()
            );
        });

        // Comment ->getAccessId() will be that of the entityGuid
        $this->eventsDispatcher->register('acl:read', 'comment', function (Event $event) {
            $params = $event->getParameters();
            $comment = $params['entity'];

            $entity = EntitiesFactory::build($comment->getAccessId());
            $user = $params['user'];

            if ($entity) {
                $canRead = ACL::_()->read($entity, $user);
                $event->setResponse($canRead);
            }
        });

        // Attachments on comments should have the same permissions as the comment parent
        // This call only happens when the 'container_guid' is not correctly set, which requires further investigation
        $this->eventsDispatcher->register('acl:read', 'object', function (Event $event) {
            $params = $event->getParameters();
            $entity = $params['entity'];

            if (!($entity instanceof Image || $entity instanceof Video)) {
                // Skip as this is not an image or a video
                return;
            }

            if (strlen($entity->getAccessId()) === 1) {
                // Skip as this is a standard access id and not a parent
                return;
            }

            $parentEntity = EntitiesFactory::build($entity->getAccessId());
            $user = $params['user'];

            if ($parentEntity) {
                $canRead = ACL::_()->read($parentEntity, $user);
                $event->setResponse($canRead);
            }
        });

        // If comment is container_guid then decide if we can allow writing
        $this->eventsDispatcher->register('acl:write:container', 'all', function (Event $event) {
            $params = $event->getParameters();
            $entity = $params['entity'];
            $user = $params['user'];
            $container = $params['container'];

            if ($container->type === 'activity' || $container->type === 'object') {
                $canInteract = ACL::_()->interact($container);
                if ($canInteract && $user->guid == $entity->owner_guid) {
                    $event->setResponse(true);
                }
            }
        });

        $this->eventsDispatcher->register('export:extender', 'comment', function (Event $event) {
            $params = $event->getParameters();
            $entity = $params['entity'];
            $attachments = $entity->getAttachments();
            $output = [];

            // Handle attachment processing.
            if ($attachments) {
                foreach ($attachments as $key => $value) {
                    $output['attachments'][$key] = $entity->getAttachment($key);
                    $output[$key] = $output['attachments'][$key];
                }
    
                // This is not a great fix. Comments need to be fully constructed at manager/repository level
                // This is not DRY or spec tested...
                if (isset($output['custom_data'])) {
                    $config = $this->getConfig();
                    $siteUrl = $config->get('site_url');
                    $cdnUrl = $config->get('cdn_url');
                    $output['custom_data']['src'] = $output['attachments']['custom_data']['src'] =
                        str_replace($siteUrl, $cdnUrl, $output['attachments']['custom_data']['src']);
                    
                    // If the container is an activity and it has a paywall, then sign any attachments so that they can be viewed.
                    $container = $this->getEntitiesBuilder()->single($entity->getEntityGuid());
                    if ($container && $container instanceof PaywallEntityInterface && $container->isPayWall()) {
                        $output['custom_data']['src'] = (new SignedUri())->sign($output['custom_data']['src']).'&unlock_paywall=true';
                    }
                }
   
                if (isset($output['custom_type']) && $output['custom_type'] === 'image') {
                    $output['custom_type'] = 'batch';
                    $output['custom_data'] = [ $output['custom_data'] ];
                }
            }

            $event->setResponse($output);
        });
    }

    /**
     * Get EntitiesBuilder from DI.
     * @return EntitiesBuilder - EntitiesBuilder instance from DI.
     */
    public function getEntitiesBuilder(): EntitiesBuilder
    {
        return Di::_()->get('EntitiesBuilder');
    }

    /**
     * Get Config from DI.
     * @return Config - Config instance from DI.
     */
    public function getConfig(): Config
    {
        return Di::_()->get('Config');
    }
}
