<?php

namespace Spec\Minds\Core\Search;

use Minds\Common\Urn;
use Minds\Core\Comments\Comment;
use Minds\Core\Entities\Ops\EntitiesOpsEvent;
use Minds\Core\Entities\Resolver;
use Minds\Core\Search\Index;
use Minds\Core\Search\SearchIndexerSubscription;
use Minds\Entities\Activity;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;

class SearchIndexerSubscriptionSpec extends ObjectBehavior
{
    protected $indexMock;
    protected $entitiesResolver;

    public function let(
        Index $indexMock,
        Resolver $entitiesResolver
    ) {
        $this->beConstructedWith($indexMock, $entitiesResolver);
        $this->indexMock = $indexMock;
        $this->entitiesResolver = $entitiesResolver;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(SearchIndexerSubscription::class);
    }

    public function it_should_index()
    {
        $event = new EntitiesOpsEvent();
        $event->setOp(EntitiesOpsEvent::OP_CREATE)
            ->setEntityUrn('urn:user:123');

        $user = new User();

        $this->entitiesResolver->setOpts([
            'cache' => false
        ])
            ->shouldBeCalled()
            ->willReturn($this->entitiesResolver);

        $this->entitiesResolver->single(new Urn('urn:user:123'))->willReturn($user);

        $this->indexMock->index($user)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->consume($event)->shouldBe(true);
    }

    public function it_should_delete()
    {
        $event = new EntitiesOpsEvent();
        $event->setOp(EntitiesOpsEvent::OP_DELETE)
            ->setEntityUrn('urn:activity:123');

        $activity = new Activity();

        $this->entitiesResolver->setOpts([
            'cache' => false
        ])
            ->shouldBeCalled()
            ->willReturn($this->entitiesResolver);

        $this->entitiesResolver->single(new Urn('urn:activity:123'))->willReturn($activity);

        $this->indexMock->remove($activity)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->consume($event)->shouldBe(true);
    }

    public function it_should_not_index_comments()
    {
        $event = new EntitiesOpsEvent();
        $event->setOp(EntitiesOpsEvent::OP_CREATE)
            ->setEntityUrn('urn:comment:1473261181828337672:0:0:0:1473273068037083150');

        $comment = new Comment();

        $this->entitiesResolver->setOpts([
            'cache' => false
        ])
            ->shouldBeCalled()
            ->willReturn($this->entitiesResolver);

        $this->entitiesResolver->single(new Urn('urn:comment:1473261181828337672:0:0:0:1473273068037083150'))->willReturn($comment);

        $this->indexMock->remove($comment)
            ->shouldNotBeCalled();

        $this->consume($event)->shouldBe(true); // True because we don't want to see again
    }
}
