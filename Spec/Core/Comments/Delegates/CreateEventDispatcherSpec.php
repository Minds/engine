<?php

namespace Spec\Minds\Core\Comments\Delegates;

use Minds\Core\Comments\Comment;
use Minds\Core\Events\Dispatcher;
use Minds\Core\EventStreams\ActionEvent;
use Minds\Core\EventStreams\Topics\ActionEventsTopic;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Minds\Core\EntitiesBuilder;
use Minds\Entities\Entity;

class CreateEventDispatcherSpec extends ObjectBehavior
{
    protected $eventsDispatcher;
    protected $entitiesBuilder;

    public function let(
        Dispatcher $eventsDispatcher,
        EntitiesBuilder $entitiesBuilder
    ) {
        $this->beConstructedWith($eventsDispatcher, $entitiesBuilder);

        $this->eventsDispatcher = $eventsDispatcher;
        $this->entitiesBuilder = $entitiesBuilder;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType('Minds\Core\Comments\Delegates\CreateEventDispatcher');
    }

    // EventsDispatcher cannot be tested yet

    public function it_should_emit_action_event(Comment $comment)
    {
        $owner = new User();

        $comment = new Comment();
        $comment->setOwnerObj($owner);
        $comment->setEntityGuid('123');
        $comment->setGuid(456);
        $comment->setParentGuidL1(0);
        $comment->setParentGuidL2(0);

        $entity = new Entity();
        $entity->guid = '654';
        $entity->urn = 'urn:entity:654';

        $actionEvent = new ActionEvent();

        $actionEvent
            ->setAction(ActionEvent::ACTION_COMMENT)
            ->setActionData(['comment_urn' => 'urn:comment:456'])
            ->setEntity($entity)
            ->setUser($comment->getOwnerEntity());

        $actionEventTopic = new ActionEventsTopic();
        $actionEventTopic->send(Argument::any())
            ->shouldBeCalled()
            ->willReturn(true);
        // $actionEventTopic->send(Argument::that(function ($actionEvent) {
        //     return $actionEvent->getAction() === 'comment';
        // }))
        //     ->shouldBeCalled()
        //     ->willReturn(true);

        $this->emitActionEvent($comment);
    }
}
