<?php

namespace Spec\Minds\Core\Notifications;

use Minds\Core\Config;
use Minds\Core\Notifications\Manager;
use Minds\Core\Notifications\Notification;
use Minds\Core\Notifications\Repository;
use Minds\Core\Notifications\Delegates\CounterDelegate;
use Minds\Core\Notifications\Delegates\PushSettingsDelegate;
use Minds\Core\Features\Manager as FeaturesManager;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ManagerSpec extends ObjectBehavior
{
    /** @var Config */
    private $config;

    /** @var Repository */
    private $repository;

    /** @var CounterDelegate $counters */
    private $counters;

    /** @var PushSettingsDelegate $settings */
    protected $settings;

    public function let(
        Config $config,
        Repository $repository,
        CounterDelegate $counters,
        PushSettingsDelegate $settings
    ) {
        $this->config = $config;
        $this->repository = $repository;
        $this->counters = $counters;
        $this->settings = $settings;

        $this->beConstructedWith($config, $repository, $counters, $settings);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }

    public function it_should_get_a_single_notification(Notification $notification, User $user)
    {
        // TODO this test is broken after polyfill update

        $this->setUser($user);
        $user->getGUID()
            ->willReturn(456);

        $this->repository->get('urn:notification:456-1234')
            ->shouldBeCalled()
            ->willReturn($notification);

        $this->getSingle('1234')->shouldReturn($notification);
    }

    public function it_should_get_from_repository_if_urn_provided(Notification $notification)
    {
        $this->repository->get('urn:notification:1234')
            ->shouldBeCalled()
            ->willReturn($notification);

        $this->getSingle('urn:notification:1234')->shouldReturn($notification);
    }

    public function it_should_add_to_repository(Notification $notification)
    {
        // TODO this test is broken after polyfill update

        $this->repository->add($notification)
            ->shouldBeCalled();

        $this->add($notification);
    }
}
