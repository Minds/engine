<?php

namespace Spec\Minds\Core\Notifications\EmailDigests;

use Minds\Core\Notifications\EmailDigests\Manager;
use Minds\Core\Notifications\EmailDigests\Repository;
use Minds\Core\Email;
use Minds\Core\Email\V2\Campaigns\Recurring\UnreadNotifications\UnreadNotifications;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Features;
use Minds\Core\Notifications\Notification;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ManagerSpec extends ObjectBehavior
{
    /** @var Repository */
    protected $repository;

    /** @var Email\Repository */
    protected $emailRepository;

    /** @var UnreadNotifications */
    protected $unreadNotificationsEmail;

    /** @var Features\Manager */
    protected $featuresManager;

    /** @var EntitiesBuilder */
    protected $entitiesBuilder;

    public function let(
        Repository $repository,
        Email\Repository $emailRepository,
        UnreadNotifications $unreadNotificationsEmail,
        Features\Manager $featuresManager,
        EntitiesBuilder $entitiesBuilder
    ) {
        $this->beConstructedWith($repository, $emailRepository, $unreadNotificationsEmail, $featuresManager, $entitiesBuilder);
        $this->repository = $repository;
        $this->emailRepository = $emailRepository;
        $this->unreadNotificationsEmail = $unreadNotificationsEmail;
        $this->featuresManager = $featuresManager;
        $this->entitiesBuilder = $entitiesBuilder;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }

    public function it_should_add_to_queue(Notification $notification)
    {
        $notification->getToGuid()
            ->willReturn('123');

        $notification->getCreatedTimestamp()
            ->willReturn(time());

        $this->repository->add(Argument::that(function ($marker) {
            return $marker->getToGuid() === '123'
                && $marker->getFrequency() === 'weekly';
        }))
            ->willReturn(true);

        $this->addToQueue($notification)
            ->shouldBe(true);
    }
}
