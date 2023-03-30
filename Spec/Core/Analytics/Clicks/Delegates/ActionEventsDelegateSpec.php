<?php

namespace Spec\Minds\Core\Analytics\Clicks\Delegates;

use Minds\Core\Analytics\Clicks\Delegates\ActionEventsDelegate;
use Minds\Core\EventStreams\ActionEvent;
use Minds\Core\EventStreams\Topics\ActionEventsTopic;
use Minds\Entities\EntityInterface;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ActionEventsDelegateSpec extends ObjectBehavior
{
    /** @var ActionEventsTopic */
    protected $actionEventsTopic;

    public function let(ActionEventsTopic $actionEventsTopic)
    {
        $this->beConstructedWith($actionEventsTopic);
        $this->actionEventsTopic = $actionEventsTopic;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(ActionEventsDelegate::class);
    }

    public function it_should_send_an_action_event_on_click(EntityInterface $entity, User $user)
    {
        $this->actionEventsTopic->send(Argument::that(function ($arg) {
            return $arg->getAction() === ActionEvent::ACTION_CLICK;
        }))->shouldBeCalled();

        $this->onClick($entity, $user);
    }
}
