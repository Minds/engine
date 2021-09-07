<?php

namespace Spec\Minds\Core\Boost\Network;

use Minds\Core\Data\MongoDB;
use Minds\Entities\Boost\Network;
use Minds\Core\Boost\Network\Manager;
use Minds\Core\Boost\Network\Boost;
use Minds\Entities\Entity;
use Minds\Core\Notifications;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ExpireSpec extends ObjectBehavior
{
    private $manager;

    /** @var Notifications\Manager */
    protected $notificationsManager;

    public function let(Manager $manager, Notifications\Manager $notificationsManager)
    {
        $this->beConstructedWith($manager, $notificationsManager);
        $this->manager = $manager;
        $this->notificationsManager = $notificationsManager;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType('Minds\Core\Boost\Network\Expire');
    }

    public function it_should_expire_a_boost(Boost $boost)
    {
        $this->manager->update($boost)
            ->shouldBeCalled();

        $boost->getState()
            ->shouldBeCalled()
            ->willReturn('created');

        $boost->setCompletedTimestamp(Argument::approximate(time() * 1000, -5))
            ->shouldBeCalled()
            ->willReturn($boost);

        $boost->getOwnerGuid()
            ->shouldBeCalled()
            ->willReturn(123);

        $boost->getEntity()
            ->shouldBeCalled()
            ->willReturn((new Entity));

        $boost->getImpressions()
            ->shouldBeCalled();

        $this->notificationsManager->add(Argument::that(function ($notification) {
            return $notification->getToGuid() === '123'
                && $notification->getEntityUrn() === 'urn:entity:';
        }))
            ->willReturn(true);

        $this->setBoost($boost);
        $this->expire();
    }
}
