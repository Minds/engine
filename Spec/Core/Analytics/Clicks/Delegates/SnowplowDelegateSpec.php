<?php

namespace Spec\Minds\Core\Analytics\Clicks\Delegates;

use Minds\Core\Analytics\Clicks\Delegates\SnowplowDelegate;
use Minds\Core\Analytics\Metrics\Event;
use Minds\Entities\EntityInterface;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;

class SnowplowDelegateSpec extends ObjectBehavior
{
    /** @var Event */
    protected $event;

    public function let(Event $event)
    {
        $this->beConstructedWith($event);
        $this->event = $event;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(SnowplowDelegate::class);
    }

    public function it_should_send_an_action_event_on_click(EntityInterface $entity, User $user)
    {
        $clientMeta = [ 'platform' => 'cli' ];
        $entityGuid = '123';

        $entity->getGuid()
            ->shouldBeCalled()
            ->willReturn($entityGuid);

        $this->event->setUser($user)
            ->shouldBeCalled()
            ->willReturn($this->event);

        $this->event->setType('action')
            ->shouldBeCalled()
            ->willReturn($this->event);

        $this->event->setAction('click')
            ->shouldBeCalled()
            ->willReturn($this->event);

        $this->event->setEntityGuid($entityGuid)
            ->shouldBeCalled()
            ->willReturn($this->event);

        $this->event->setClientMeta($clientMeta)
            ->shouldBeCalled()
            ->willReturn($this->event);

        $this->event->push(shouldIndex: false)
            ->shouldBeCalled();

        $this->onClick($entity, $clientMeta, $user);
    }
}
