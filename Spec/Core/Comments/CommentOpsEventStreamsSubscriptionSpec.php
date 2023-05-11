<?php

namespace Spec\Minds\Core\Comments;

use Minds\Core\Comments\Comment;
use Minds\Core\Comments\CommentOpsEventStreamsSubscription;
use Minds\Core\Comments\Manager;
use Minds\Core\Comments\RelationalRepository;
use Minds\Core\Comments\SearchRepository;

use Minds\Core\EventStreams\ActionEvent;
use Minds\Core\Entities\Ops\EntitiesOpsEvent;

use Prophecy\Argument;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;

class CommentOpsEventStreamsSubscriptionSpec extends ObjectBehavior
{
    private Collaborator $manager;
    private Collaborator $relationalRepository;
    private Collaborator $searchRepository;


    public function let(
        Manager $manager,
        RelationalRepository $relationalRepository,
        SearchRepository $searchRepository
    ) {
        $this->manager = $manager;
        $this->relationalRepository = $relationalRepository;
        $this->searchRepository = $searchRepository;
        $this->beConstructedWith($manager, $relationalRepository, $searchRepository);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(CommentOpsEventStreamsSubscription::class);
    }

    public function it_should_ack_when_not_op_event(ActionEvent $event)
    {
        $this->consume($event)->shouldReturn(true);
    }

    public function it_should_ack_when_not_comment(EntitiesOpsEvent $event)
    {
        $event->getEntityUrn()->shouldBeCalled()->willReturn('urn:not-comment:123');
        $this->consume($event)->shouldReturn(true);
    }

    public function it_should_delete(EntitiesOpsEvent $event)
    {
        $this->relationalRepository->delete('123')
            ->shouldBeCalled()
            ->willReturn(true);

        $this->searchRepository->delete('123')
            ->shouldBeCalled()
            ->willReturn(true);

        $event->getEntityUrn()->shouldBeCalled()->willReturn('urn:comment:123');
        $event->getOp()->shouldBeCalled()->willReturn('delete');
        $this->consume($event)->shouldReturn(true);
    }

    public function it_should_ack_when_comment_not_found(EntitiesOpsEvent $event)
    {
        $event->getEntityUrn()->shouldBeCalled()->willReturn('urn:comment:123');
        $event->getOp()->shouldBeCalled()->willReturn('update');
        $this->manager->getByUrn('urn:comment:123', true)->shouldBeCalled()->willReturn(null);
        $this->consume($event)->shouldReturn(true);
    }

    public function it_should_add_root_comment(EntitiesOpsEvent $event, Comment $comment)
    {
        $event->getEntityUrn()->shouldBeCalled()->willReturn('urn:comment:123');
        $event->getOp()->shouldBeCalled()->willReturn('update');
        $this->manager->getByUrn('urn:comment:123', true)->shouldBeCalled()->willReturn($comment);

        $comment->getTimeCreated()->shouldBeCalled()->willReturn(123);
        $comment->getParentGuidL1()->shouldBeCalled()->willReturn(0);
        $comment->getParentGuidL2()->shouldBeCalled()->willReturn(0);

        $this->relationalRepository
            ->add($comment, Argument::type('string'), Argument::type('string'), null, 0)
            ->shouldBeCalled()
            ->willReturn(true);
        $this->searchRepository
            ->add($comment, Argument::type('string'), Argument::type('string'), null, 0)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->consume($event)->shouldReturn(true);
    }

    public function it_should_add_l1_comment(EntitiesOpsEvent $event, Comment $comment)
    {
        $event->getEntityUrn()->shouldBeCalled()->willReturn('urn:comment:123');
        $event->getOp()->shouldBeCalled()->willReturn('update');
        $this->manager->getByUrn('urn:comment:123', true)->shouldBeCalled()->willReturn($comment);

        $comment->getTimeCreated()->shouldBeCalled()->willReturn(123);
        $comment->getParentGuidL1()->shouldBeCalled()->willReturn('456');
        $comment->getParentGuidL2()->shouldBeCalled()->willReturn(0);

        $this->relationalRepository
            ->add($comment, Argument::type('string'), Argument::type('string'), '456', 1)
            ->shouldBeCalled()
            ->willReturn(true);
        $this->searchRepository
            ->add($comment, Argument::type('string'), Argument::type('string'), '456', 1)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->consume($event)->shouldReturn(true);
    }

    public function it_should_add_l2_comment(EntitiesOpsEvent $event, Comment $comment)
    {
        $event->getEntityUrn()->shouldBeCalled()->willReturn('urn:comment:123');
        $event->getOp()->shouldBeCalled()->willReturn('update');
        $this->manager->getByUrn('urn:comment:123', true)->shouldBeCalled()->willReturn($comment);

        $comment->getTimeCreated()->shouldBeCalled()->willReturn(123);
        $comment->getParentGuidL2()->shouldBeCalled()->willReturn('768');

        $this->relationalRepository
            ->add($comment, Argument::type('string'), Argument::type('string'), '768', 2)
            ->shouldBeCalled()
            ->willReturn(true);
        $this->searchRepository
            ->add($comment, Argument::type('string'), Argument::type('string'), '768', 2)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->consume($event)->shouldReturn(true);
    }
}
