<?php

namespace Spec\Minds\Core\Channels;

use Minds\Core\Channels\Delegates\Artifacts\ArtifactsDelegateInterface;
use Minds\Core\Channels\Manager;
use Minds\Core\Channels\Delegates;
use Minds\Core\Channels\Delegates\Artifacts;
use Minds\Core\Queue\Interfaces\QueueClient;
use Minds\Entities\User;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ManagerSpec extends ObjectBehavior
{
    /** @var string[] */
    protected $artifactsDelegates;

    /** @var Delegates\Artifacts\Factory */
    protected $artifactsDelegatesFactory;

    /** @var Delegates\Logout */
    protected $logoutDelegate;

    /** @var  QueueClient */
    protected $queueClient;

    function let(
        Delegates\Artifacts\Factory $artifactsDelegatesFactory,
        Delegates\Logout $logoutDelegate,
        QueueClient $queueClient
    )
    {
        $this->beConstructedWith(
            $artifactsDelegatesFactory,
            $logoutDelegate,
            $queueClient
        );

        $this->artifactsDelegatesFactory = $artifactsDelegatesFactory;
        $this->logoutDelegate = $logoutDelegate;
        $this->queueClient = $queueClient;
    }

    function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }

    function it_should_snapshot_a_channel(
        User $user,
        ArtifactsDelegateInterface $artifactsDelegateMock
    )
    {
        $user->get('guid')
            ->shouldBeCalled()
            ->willReturn(1000);

        $deletionDelegates = [
            Artifacts\EntityDelegate::class,
            Artifacts\LookupDelegate::class,
            Artifacts\UserIndexesDelegate::class,
            Artifacts\UserEntitiesDelegate::class,
            Artifacts\SubscribersDelegate::class,
            Artifacts\SubscriptionsDelegate::class,
            Artifacts\ElasticsearchDocumentsDelegate::class,
            Artifacts\CommentsDelegate::class,
        ];

        foreach ($deletionDelegates as $deletionDelegate) {
            $this->artifactsDelegatesFactory->build($deletionDelegate)
                ->shouldBeCalled()
                ->willReturn($artifactsDelegateMock);
        }

        $artifactsDelegateMock->snapshot(1000)
            ->shouldBeCalledTimes(count($deletionDelegates))
            ->willReturn(true);

        $this
            ->setUser($user)
            ->snapshot()
            ->shouldReturn(true);
    }

    function it_should_delete_a_channel(
        User $user
    )
    {
        $user->get('guid')
            ->shouldBeCalled()
            ->willReturn(1000);

        $this->queueClient->setQueue('ChannelDeferredOps')
            ->shouldBeCalled()
            ->willReturn($this->queueClient);

        $this->queueClient->send([
            'type' => 'delete',
            'user_guid' => 1000,
        ])
            ->shouldBeCalled()
            ->willReturn(true);

        $this->logoutDelegate->logout($user)
            ->shouldBeCalled()
            ->willReturn(true);

        $this
            ->setUser($user)
            ->delete()
            ->shouldReturn(true);
    }

    function it_should_cleanup_a_deleted_channel(
        User $user,
        ArtifactsDelegateInterface $artifactsDelegateMock
    )
    {
        $user->get('guid')
            ->shouldBeCalled()
            ->willReturn(1000);

        $deletionDelegates = [
            Artifacts\EntityDelegate::class,
            Artifacts\LookupDelegate::class,
            Artifacts\UserIndexesDelegate::class,
            Artifacts\UserEntitiesDelegate::class,
            Artifacts\SubscribersDelegate::class,
            Artifacts\SubscriptionsDelegate::class,
            Artifacts\ElasticsearchDocumentsDelegate::class,
            Artifacts\CommentsDelegate::class,
        ];

        foreach ($deletionDelegates as $deletionDelegate) {
            $this->artifactsDelegatesFactory->build($deletionDelegate)
                ->shouldBeCalled()
                ->willReturn($artifactsDelegateMock);
        }

        $artifactsDelegateMock->delete(1000)
            ->shouldBeCalledTimes(count($deletionDelegates))
            ->willReturn(true);

        $this->logoutDelegate->logout($user)
            ->shouldBeCalled()
            ->willReturn(true);

        $this
            ->setUser($user)
            ->deleteCleanup()
            ->shouldReturn(true);
    }

    function it_should_restore_a_channel(
        ArtifactsDelegateInterface $artifactsDelegateMock
    )
    {
        $deletionDelegates = [
            Artifacts\EntityDelegate::class,
            Artifacts\LookupDelegate::class,
            Artifacts\UserIndexesDelegate::class,
            Artifacts\UserEntitiesDelegate::class,
            Artifacts\SubscribersDelegate::class,
            Artifacts\SubscriptionsDelegate::class,
            Artifacts\ElasticsearchDocumentsDelegate::class,
            Artifacts\CommentsDelegate::class,
        ];

        foreach ($deletionDelegates as $deletionDelegate) {
            $this->artifactsDelegatesFactory->build($deletionDelegate)
                ->shouldBeCalled()
                ->willReturn($artifactsDelegateMock);
        }

        $artifactsDelegateMock->restore(1000)
            ->shouldBeCalledTimes(count($deletionDelegates))
            ->willReturn(true);

        $this
            ->restore(1000)
            ->shouldReturn(true);
    }
}
