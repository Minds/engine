<?php

namespace Spec\Minds\Core\Security\Block\Delegates;

use Minds\Core\Analytics\Metrics\Event;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Security\Block\BlockEntry;
use Minds\Core\Security\Block\Delegates\AnalyticsDelegate;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;

class AnalyticsDelegateSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(AnalyticsDelegate::class);
    }

    public function it_should_add_block_event(
        Event $event,
        EntitiesBuilder $entitiesBuilder
    ) {
        $this->beConstructedWith($entitiesBuilder, $event);

        $actor = new User();
        $actor->set('guid', '123');

        $subject = new User();
        $subject->set('guid', '456');

        $entitiesBuilder->single('123')
            ->willReturn($actor);

        $event->setType('action')
            ->willReturn($event);
        $event->setAction('block')
            ->willReturn($event);
        $event->setProduct('platform')
            ->willReturn($event);
        $event->setUserGuid('123')
            ->willReturn($event);
        $event->setUserPhoneNumberHash(null)
            ->willReturn($event);
        $event->setEntityGuid('456')
            ->willReturn($event);
        $event->setEntityType('user')
            ->willReturn($event);

        $event->push()->shouldBeCalled();

        $blockEntry = new BlockEntry();
        $blockEntry->setActor($actor);
        $blockEntry->setSubject($subject);

        $this->onAdd($blockEntry);
    }

    public function it_should_add_unlock_event(
        Event $event,
        EntitiesBuilder $entitiesBuilder
    ) {
        $this->beConstructedWith($entitiesBuilder, $event);

        $actor = new User();
        $actor->set('guid', '123');

        $subject = new User();
        $subject->set('guid', '456');

        $entitiesBuilder->single('123')
            ->willReturn($actor);

        $event->setType('action')
            ->willReturn($event);
        $event->setAction('unblock')
            ->willReturn($event);
        $event->setProduct('platform')
            ->willReturn($event);
        $event->setUserGuid('123')
            ->willReturn($event);
        $event->setUserPhoneNumberHash(null)
            ->willReturn($event);
        $event->setEntityGuid('456')
            ->willReturn($event);
        $event->setEntityType('user')
            ->willReturn($event);

        $event->push()->shouldBeCalled();

        $blockEntry = new BlockEntry();
        $blockEntry->setActor($actor);
        $blockEntry->setSubject($subject);

        $this->onDelete($blockEntry);
    }
}
