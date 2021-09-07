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
    protected $actionEventTopic;

    public function let(
        Dispatcher $eventsDispatcher,
        EntitiesBuilder $entitiesBuilder,
        ActionEventsTopic $actionEventTopic
    ) {
        $this->beConstructedWith($eventsDispatcher, $entitiesBuilder, $actionEventTopic);

        $this->eventsDispatcher = $eventsDispatcher;
        $this->entitiesBuilder = $entitiesBuilder;
        $this->actionEventTopic = $actionEventTopic;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType('Minds\Core\Comments\Delegates\CreateEventDispatcher');
    }

    // EventsDispatcher cannot be tested yet

    public function it_should_emit_action_event(Comment $comment)
    {
        $comment->getEntityGuid()->willReturn('654');
        $comment->getUrn()->willReturn('urn:comment:123');
        $comment->getOwnerEntity()->willReturn(new User());

        $entity = new Entity();
        $entity->guid = '654';

        $this->entitiesBuilder->single('654')->willReturn($entity);

        $this->actionEventTopic->send(Argument::that(function ($actionEvent) {
            return $actionEvent->getAction() === 'comment';
        }))
            ->shouldBeCalled()
            ->willReturn(true);

        $this->emitActionEvent($comment);
    }
}
