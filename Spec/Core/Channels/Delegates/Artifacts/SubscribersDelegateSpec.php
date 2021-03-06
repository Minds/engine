<?php

namespace Spec\Minds\Core\Channels\Delegates\Artifacts;

use Minds\Core\Channels\Delegates\Artifacts\SubscribersDelegate;
use Minds\Core\Channels\Snapshots\Repository;
use Minds\Core\Channels\Snapshots\Snapshot;
use Minds\Core\Data\Cassandra\Prepared\Custom;
use Minds\Core\Data\Cassandra\Scroll;
use Minds\Core\Subscriptions\Manager as SubscriptionsManager;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class SubscribersDelegateSpec extends ObjectBehavior
{
    /** @var Repository */
    protected $repository;

    /** @var Scroll */
    protected $scroll;

    /** @var SubscriptionsManager */
    protected $subscriptionsManager;

    public function let(
        Repository $repository,
        Scroll $scroll,
        SubscriptionsManager $subscriptionsManager
    ) {
        $this->beConstructedWith($repository, $scroll, $subscriptionsManager);
        $this->repository = $repository;
        $this->scroll = $scroll;
        $this->subscriptionsManager = $subscriptionsManager;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(SubscribersDelegate::class);
    }

    public function it_should_snapshot()
    {
        $this->scroll->request(Argument::that(function (Custom $prepared) {
            $query = $prepared->build();

            return stripos($query['string'], 'select * from friendsof') !== false &&
                $query['values'] === ['1000'];
        }))
            ->shouldBeCalled()
            ->willReturn([
                ['key' => '1000', 'column1' => '1001', 'value' => '123123'],
                ['key' => '1000', 'column1' => '1002', 'value' => '123123'],
            ]);

        $this->repository->add(Argument::that(function (Snapshot $snapshot) {
            return $snapshot->getUserGuid() === 1000 && $snapshot->getType() === 'friendsof';
        }))
            ->shouldBeCalledTimes(2)
            ->willReturn(true);

        $this
            ->snapshot(1000)
            ->shouldReturn(true);
    }

    public function it_should_restore(
        Snapshot $snapshotMock
    ) {
        $this->repository->getList([
            'user_guid' => 1000,
            'type' => 'friendsof',
        ])
            ->shouldBeCalled()
            ->willReturn([$snapshotMock, $snapshotMock]);

        $snapshotMock->getJsonData()
            ->shouldBeCalled()
            ->willReturn(['key' => '1000', 'column1' => '1010', 'value' => '123123']);

        $this->subscriptionsManager->setSubscriber(Argument::that(function (User $user) {
            return $user->guid == 1010;
        }))
            ->shouldBeCalledTimes(2)
            ->willReturn($this->subscriptionsManager);

        $this->subscriptionsManager->setSendEvents(false)
            ->shouldBeCalledTimes(2)
            ->willReturn($this->subscriptionsManager);

        $this->subscriptionsManager->subscribe(Argument::that(function (User $user) {
            return $user->guid == 1000;
        }))
            ->shouldBeCalledTimes(2);

        $this
            ->restore(1000)
            ->shouldReturn(true);
    }

    public function it_should_hide()
    {
        $this->scroll->request(Argument::that(function (Custom $prepared) {
            $query = $prepared->build();

            return stripos($query['string'], 'select * from friendsof') !== false &&
                $query['values'] === ['1000'];
        }))
            ->shouldBeCalled()
            ->willReturn([
                ['key' => '1000', 'column1' => '1001', 'value' => '123123'],
                ['key' => '1000', 'column1' => '1002', 'value' => '123123'],
            ]);


        $this->subscriptionsManager->setSubscriber(Argument::that(function (User $user) {
            return $user->guid == 1001;
        }))
            ->willReturn($this->subscriptionsManager);

        $this->subscriptionsManager->setSubscriber(Argument::that(function (User $user) {
            return $user->guid == 1002;
        }))
            ->willReturn($this->subscriptionsManager);

        $this->subscriptionsManager->setSendEvents(false)
            ->shouldBeCalledTimes(2)
            ->willReturn($this->subscriptionsManager);

        $this->subscriptionsManager->unSubscribe(Argument::that(function (User $user) {
            return $user->guid == 1000;
        }))
            ->shouldBeCalledTimes(2)
            ->willReturn(true);

        $this
            ->hide(1000)
            ->shouldReturn(true);
    }

    public function it_should_delete()
    {
        $this->scroll->request(Argument::that(function (Custom $prepared) {
            $query = $prepared->build();

            return stripos($query['string'], 'select * from friendsof') !== false &&
                $query['values'] === ['1000'];
        }))
            ->shouldBeCalled()
            ->willReturn([
                ['key' => '1000', 'column1' => '1001', 'value' => '123123'],
                ['key' => '1000', 'column1' => '1002', 'value' => '123123'],
            ]);


        $this->subscriptionsManager->setSubscriber(Argument::that(function (User $user) {
            return $user->guid == 1001;
        }))
            ->willReturn($this->subscriptionsManager);

        $this->subscriptionsManager->setSubscriber(Argument::that(function (User $user) {
            return $user->guid == 1002;
        }))
            ->willReturn($this->subscriptionsManager);

        $this->subscriptionsManager->setSendEvents(false)
            ->shouldBeCalledTimes(2)
            ->willReturn($this->subscriptionsManager);

        $this->subscriptionsManager->unSubscribe(Argument::that(function (User $user) {
            return $user->guid == 1000;
        }))
            ->shouldBeCalledTimes(2)
            ->willReturn(true);

        $this
            ->delete(1000)
            ->shouldReturn(true);
    }
}
