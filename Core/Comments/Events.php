<?php

/**
 * Minds Comments Events Listeners
 *
 * @author emi
 */

namespace Minds\Core\Comments;

use Minds\Core\Di\Di;
use Minds\Core\Events\Dispatcher;
use Minds\Core\Events\Event;
use Minds\Entities\Factory as EntitiesFactory;
use Minds\Core\Votes\Vote;
use Minds\Core\Sockets;
use Minds\Core\Session;
use Minds\Core\Security\ACL;

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

        // If comment is container_guid then decide if we can allow access
        $this->eventsDispatcher->register('acl:read', 'all', function (Event $event) {
            $params = $event->getParameters();
            $entity = $params['entity'];
            $user = $params['user'];

            $container = EntitiesFactory::build($entity->container_guid);

            // If the container container_guid is the same as the the container owner
            if ($container
                && $container->container_guid == $container->owner_guid
                && ACL::_()->read($container)
            ) {
                $event->setResponse(true);
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
    }
}
