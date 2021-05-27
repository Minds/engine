<?php

/**
 * Description
 *
 * @author emi
 */

namespace Minds\Core\Comments\Delegates;

use Minds\Core\Comments\Comment;
use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Events\Dispatcher;
use Minds\Core\EventStreams\ActionEvent;
use Minds\Core\EventStreams\Topics\ActionEventsTopic;

class CreateEventDispatcher
{
    /** @var Dispatcher */
    protected $eventsDispatcher;

    /** @var EntitiesBuilder */
    protected $entitiesBuilder;

    public function __construct($eventsDispatcher = null, EntitiesBuilder $entitiesBuilder = null)
    {
        $this->eventsDispatcher = $eventsDispatcher ?: Di::_()->get('EventsDispatcher');
        $this->entitiesBuilder = $entitiesBuilder ?? Di::_()->get('EntitiesBuilder');
    }

    public function dispatch(Comment $comment)
    {
        $this->eventsDispatcher->trigger('create', 'elgg/event/comment', $comment);
        $this->eventsDispatcher->trigger('save', 'comment', [ 'entity' => $comment ]);

        $this->emitActionEvent($comment);
    }

    public function emitActionEvent(Comment $comment)
    {
        $entity = $this->entitiesBuilder->single($comment->getEntityGuid());

        $actionEvent = new ActionEvent();
        $actionEvent
            ->setAction(ActionEvent::ACTION_COMMENT)
            ->setActionData([
                'comment_urn' => $comment->getUrn(),
            ])
            ->setEntity($entity)
            ->setUser($comment->getOwnerEntity());

        $actionEventTopic = new ActionEventsTopic();
        $actionEventTopic->send($actionEvent);
    }
}
