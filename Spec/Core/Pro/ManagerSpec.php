<?php

namespace Spec\Minds\Core\Pro;

use Minds\Entities\User;
use Minds\Core\Pro\Manager;
use Minds\Core\Pro\Delegates\SubscriptionDelegate;
use Minds\Core\Entities\Actions\Save;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ManagerSpec extends ObjectBehavior
{
    private $subscriptionDelegate;
    private $saveAction;

    public function let(SubscriptionDelegate $subscriptionDelegate, Save $saveAction)
    {
        $this->beConstructedWith(null, $saveAction, null, null, null, null, $subscriptionDelegate);
        $this->subscriptionDelegate = $subscriptionDelegate;
        $this->saveAction = $saveAction;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }

    public function it_should_delete_subscription(User $user)
    {
        $this->setUser($user);

        $this->subscriptionDelegate
             ->onDisable($user)
             ->shouldBeCalled();

        $this->saveAction
            ->setEntity($user)
            ->shouldBeCalled()
            ->willReturn($this->saveAction);

        $this->saveAction
            ->save()
            ->shouldBeCalled()
            ->willReturn(true);

        $this->disable();
    }
}
