<?php

namespace Spec\Minds\Core\Notification;

use Minds\Core\Config;
use Minds\Core\Notification\LegacyRepository;
use Minds\Core\Notification\Manager;
use Minds\Core\Notification\Notification;
use Minds\Core\Notification\Repository;
use Minds\Core\Notification\CassandraRepository;
use Minds\Core\Notification\Counters;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ManagerSpec extends ObjectBehavior
{
    /** @var Config */
    private $config;

    /** @var CassandraRepository */
    private $cassandraRepository;

    public function let(
        Config $config,
        CassandraRepository $cassandraRepository,
        Counters $counters
    ) {
        $this->config = $config;
        $this->cassandraRepository = $cassandraRepository;

        $this->beConstructedWith($config, $cassandraRepository, $counters);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }

    public function it_should_get_a_single_notification(User $user)
    {
        $notification = new Notification();

        $this->setUser($user);
        $user->getGUID()
            ->willReturn(456);

        $this->cassandraRepository->get('urn:notification:456-1234')
            ->shouldBeCalled()
            ->willReturn($notification);

        $this->getSingle('1234')->shouldReturn($notification);
    }

    public function it_should_get_from_cassandra_if_urn_provided(Notification $notification)
    {
        $this->cassandraRepository->get('urn:notification:1234')
            ->shouldBeCalled()
            ->willReturn($notification);

        $this->getSingle('urn:notification:1234')->shouldReturn($notification);
    }

    public function it_should_get_list_from_cassandra(Notification $notification)
    {
        $this->cassandraRepository->getList(Argument::that(function ($opts) {
            return $opts['limit'] === 6;
        }))
            ->shouldBeCalled()
            ->willReturn([ $notification ]);

        $response = $this->getList([ 'limit' => 6 ]);
        $response[0]->shouldBe($notification);
    }

    public function it_should_add_to_both_repositories(Notification $notification)
    {
        $this->cassandraRepository->add($notification)
            ->shouldBeCalled();

        $this->add($notification);
    }
}
