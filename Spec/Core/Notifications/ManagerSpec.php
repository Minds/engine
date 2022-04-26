<?php

namespace Spec\Minds\Core\Notifications;

use Minds\Core\Notifications\Repository;
use PhpSpec\ObjectBehavior;
use Minds\Core\Comments\Manager as CommentsManager;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Notifications\Manager;
use Minds\Core\Notifications\Notification;
use Minds\Core\Notifications\NotificationsListOpts;
use Minds\Core\Security\ACL;
use Minds\Entities\User;
use PhpSpec\Exception\Example\FailureException;

class ManagerSpec extends ObjectBehavior
{
    protected $repository;
    protected $commentsManager;
    protected $delegates;
    protected $acl;
    protected $entitiesBuilder;

    public function let(
        Repository $repository,
        CommentsManager $commentsManager,
        ACL $acl,
        EntitiesBuilder $entitiesBuilder
    ) {
        $this->beConstructedWith($repository, $commentsManager, [], $acl, $entitiesBuilder);
        $this->repository = $repository;
        $this->commentsManager = $commentsManager;
        $this->delegates = [];
        $this->acl = $acl;
        $this->entitiesBuilder = $entitiesBuilder;
    }


    public function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }

    public function it_should_get_list(
        Notification $notification1,
        User $sender
    ) {
        $opts = new NotificationsListOpts();
        $opts->setMerge(false);

        $notification1->getFromGuid()->willReturn(123);

        $this->repository->getList($opts)
            ->shouldBeCalled()
            ->willReturn([
                [ $notification1 ]
            ]);

        $this->entitiesBuilder->single(123)
            ->shouldBeCalled()
            ->willReturn($sender);
        
        $this->acl->read($sender)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->getList($opts)->shouldBeAGenerator([[$notification1]]);
    }

    public function it_should_filter_senders_for_which_acl_read_fails(
        Notification $notification1,
        User $sender
    ) {
        $opts = new NotificationsListOpts();
        $opts->setMerge(false);

        $notification1->getFromGuid()->willReturn(123);

        $this->repository->getList($opts)
            ->shouldBeCalled()
            ->willReturn([
                [ $notification1 ]
            ]);

        $this->entitiesBuilder->single(123)
            ->shouldBeCalled()
            ->willReturn($sender);
        
        $this->acl->read($sender)
            ->shouldBeCalled()
            ->willReturn(false);

        $this->getList($opts)->shouldBeAGenerator([]);
    }

    public function getMatchers(): array
    {
        $matchers = [];

        $matchers['beAGenerator'] = function ($subject, $items) {
            $subjectItems = iterator_to_array($subject);

            if ($subjectItems !== $items) {
                throw new FailureException(sprintf("Subject should be a traversable containing %s, but got %s.", json_encode($items), json_encode($subjectItems)));
            }

            return true;
        };

        return $matchers;
    }
}
