<?php
declare(strict_types=1);

namespace Spec\Minds\Core\Search;

use Minds\Common\Urn;
use Minds\Core\Comments\Comment;
use Minds\Core\Entities\Ops\EntitiesOpsEvent;
use Minds\Core\Entities\Resolver;
use Minds\Core\Hashtags\WelcomeTag\Manager as WelcomeTagManager;
use Minds\Core\Log\Logger;
use Minds\Core\Search\Index;
use Minds\Core\Search\SearchIndexerSubscription;
use Minds\Entities\Activity;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;

class SearchIndexerSubscriptionSpec extends ObjectBehavior
{
    protected Collaborator $indexMock;
    protected Collaborator $entitiesResolver;
    protected Collaborator $logger;
    protected Collaborator $welcomeTagManager;

    public function let(
        Index $indexMock,
        Resolver $entitiesResolver,
        Logger $logger,
        WelcomeTagManager $welcomeTagManager,
    ) {
        $this->indexMock = $indexMock;
        $this->entitiesResolver = $entitiesResolver;
        $this->logger = $logger;
        $this->welcomeTagManager = $welcomeTagManager;

        $this->beConstructedWith(
            $indexMock,
            $entitiesResolver,
            $logger,
            $welcomeTagManager
        );
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(SearchIndexerSubscription::class);
    }

    public function it_should_index_a_user()
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

    public function it_should_index_an_activity()
    {
        $event = new EntitiesOpsEvent();
        $event->setOp(EntitiesOpsEvent::OP_CREATE)
            ->setEntityUrn('urn:user:123');

        $activity = new Activity();

        $this->entitiesResolver->setOpts([
            'cache' => false
        ])
            ->shouldBeCalled()
            ->willReturn($this->entitiesResolver);

        $this->entitiesResolver->single(new Urn('urn:user:123'))->willReturn($activity);

        $this->welcomeTagManager->strip($activity)
            ->shouldBeCalled()
            ->willReturn($activity);

        $this->welcomeTagManager->shouldAppend($activity)
            ->shouldBeCalled()
            ->willReturn(false);

        $this->indexMock->index($activity)
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

        $this->welcomeTagManager->strip($activity)
            ->shouldBeCalled()
            ->willReturn($activity);

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

    public function it_should_index_an_activity_with_patched_tags_when_should_append_is_true()
    {
        $event = new EntitiesOpsEvent();
        $event->setOp(EntitiesOpsEvent::OP_CREATE)
            ->setEntityUrn('urn:user:123');

        $activity = new Activity();

        $this->entitiesResolver->setOpts([
            'cache' => false
        ])
            ->shouldBeCalled()
            ->willReturn($this->entitiesResolver);

        $this->entitiesResolver->single(new Urn('urn:user:123'))->willReturn($activity);

        $this->welcomeTagManager->strip($activity)
            ->shouldBeCalled()
            ->willReturn($activity);

        $this->welcomeTagManager->shouldAppend($activity)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->welcomeTagManager->append($activity)
            ->shouldBeCalled()
            ->willReturn($activity);

        $this->indexMock->index($activity)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->consume($event)->shouldBe(true);
    }
}
