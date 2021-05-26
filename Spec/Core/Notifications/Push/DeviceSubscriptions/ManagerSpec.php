<?php

namespace Spec\Minds\Core\Notifications\Push\DeviceSubscriptions;

use Minds\Core\Notifications\Push\DeviceSubscriptions\DeviceSubscription;
use Minds\Core\Notifications\Push\DeviceSubscriptions\DeviceSubscriptionListOpts;
use Minds\Core\Notifications\Push\DeviceSubscriptions\Manager;
use Minds\Core\Notifications\Push\DeviceSubscriptions\Repository;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ManagerSpec extends ObjectBehavior
{
    /** @var Repository */
    protected $repository;

    public function let(Repository $repository)
    {
        $this->beConstructedWith($repository);
        $this->repository = $repository;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }

    public function it_should_get_a_list_of_devices()
    {
        $opts = new DeviceSubscriptionListOpts();

        $this->repository->getList($opts)
            ->willReturn([new DeviceSubscription]);

        $this->getList($opts)
            ->shouldHaveCount(1);
    }

    public function it_should_add_a_device(DeviceSubscription $deviceSubscription)
    {
        $this->repository->add($deviceSubscription)
            ->willReturn(true);
        $this->add($deviceSubscription);
    }

    public function it_should_delete_a_device(DeviceSubscription $deviceSubscription)
    {
        $this->repository->delete($deviceSubscription)
            ->willReturn(true);
        $this->delete($deviceSubscription);
    }
}
