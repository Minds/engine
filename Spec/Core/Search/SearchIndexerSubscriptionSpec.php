<?php

namespace Spec\Minds\Core\Search;

use Minds\Common\Urn;
use Minds\Core\Blogs\Blog;
use Minds\Core\Comments\Comment;
use Minds\Core\Data\cache\PsrWrapper;
use Minds\Core\Entities\Ops\EntitiesOpsEvent;
use Minds\Core\Entities\Resolver;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Feeds\User\Manager as FeedsUserManager;
use Minds\Core\Log\Logger;
use Minds\Core\Search\Index;
use Minds\Core\Search\SearchIndexerSubscription;
use Minds\Entities\Activity;
use Minds\Entities\Image;
use Minds\Entities\User;
use Minds\Entities\Video;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;

class SearchIndexerSubscriptionSpec extends ObjectBehavior
{
    protected Collaborator $indexMock;
    protected Collaborator $entitiesResolver;
    protected Collaborator $entitiesBuilder;
    protected Collaborator $feedUserManager;
    protected Collaborator $logger;

    public function let(
        Index $indexMock,
        Resolver $entitiesResolver,
        EntitiesBuilder $entitiesBuilder,
        FeedsUserManager $feedUserManager,
        Logger $logger
    ) {
        $this->indexMock = $indexMock;
        $this->entitiesResolver = $entitiesResolver;
        $this->entitiesBuilder = $entitiesBuilder;
        $this->feedUserManager = $feedUserManager;
        $this->logger = $logger;

        $this->beConstructedWith(
            $indexMock,
            $entitiesResolver,
            $entitiesBuilder,
            $feedUserManager,
            $logger
        );
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

    // Activity patched tags.

    public function it_should_index_an_activity_with_patched_tags_when_a_user_has_not_yet_posted(
        User $user
    ) {
        $entityUrn = 'urn:activity:123';
        $ownerGuid = '234';

        $event = new EntitiesOpsEvent();
        $event->setOp(EntitiesOpsEvent::OP_CREATE)
            ->setEntityUrn($entityUrn);

        $entity = new Activity();
        $entity->owner_guid = $ownerGuid;

        $this->entitiesResolver->setOpts([
            'cache' => false
        ])
            ->shouldBeCalled()
            ->willReturn($this->entitiesResolver);

        $this->entitiesResolver->single(new Urn($entityUrn))
            ->shouldBeCalled()
            ->willReturn($entity);

        $this->feedUserManager->getHasMadePostsFromCache($ownerGuid)
            ->shouldBeCalled()
            ->willReturn(false);

        $this->entitiesBuilder->single($ownerGuid)
            ->shouldBeCalled()
            ->willReturn($user);
        
        $this->feedUserManager->setHasMadePostsInCache($ownerGuid)
            ->shouldBeCalled();

        $this->feedUserManager->setUser($user)
            ->shouldBeCalled()
            ->willReturn($this->feedUserManager);

        $this->feedUserManager->hasMadePosts()
            ->shouldBeCalled()
            ->willReturn(false);

        $this->indexMock->index(Argument::that(function ($arg) {
            return $arg->getTags() === ['hellominds'];
        }))
            ->shouldBeCalled()
            ->willReturn(true);

        $this->consume($event)->shouldBe(true);
    }

    public function it_should_index_an_activity_with_patched_tags_when_a_user_has_not_yet_posted_but_posts_with_tags_already(
        User $user
    ) {
        $entityUrn = 'urn:activity:123';
        $ownerGuid = '234';

        $event = new EntitiesOpsEvent();
        $event->setOp(EntitiesOpsEvent::OP_CREATE)
            ->setEntityUrn($entityUrn);

        $entity = new Activity();
        $entity->owner_guid = $ownerGuid;
        $entity->setTags(['tag1']);

        $this->entitiesResolver->setOpts([
            'cache' => false
        ])
            ->shouldBeCalled()
            ->willReturn($this->entitiesResolver);

        $this->entitiesResolver->single(new Urn($entityUrn))
            ->shouldBeCalled()
            ->willReturn($entity);

        $this->feedUserManager->getHasMadePostsFromCache($ownerGuid)
            ->shouldBeCalled()
            ->willReturn(false);

        $this->entitiesBuilder->single($ownerGuid)
            ->shouldBeCalled()
            ->willReturn($user);
        
        $this->feedUserManager->setHasMadePostsInCache($ownerGuid)
            ->shouldBeCalled();

        $this->feedUserManager->setUser($user)
            ->shouldBeCalled()
            ->willReturn($this->feedUserManager);

        $this->feedUserManager->hasMadePosts()
            ->shouldBeCalled()
            ->willReturn(false);

        $this->indexMock->index(Argument::that(function ($arg) {
            return $arg->getTags() === ['tag1', 'hellominds'];
        }))
            ->shouldBeCalled()
            ->willReturn(true);

        $this->consume($event)->shouldBe(true);
    }

    public function it_should_index_an_activity_without_patched_tags_when_a_user_already_has_posted_when_looking_in_es(
        User $user
    ) {
        $entityUrn = 'urn:activity:123';
        $ownerGuid = '234';

        $event = new EntitiesOpsEvent();
        $event->setOp(EntitiesOpsEvent::OP_CREATE)
            ->setEntityUrn($entityUrn);

        $entity = new Activity();
        $entity->owner_guid = $ownerGuid;
        $entity->setTags(['tag1']);

        $this->entitiesResolver->setOpts([
            'cache' => false
        ])
            ->shouldBeCalled()
            ->willReturn($this->entitiesResolver);

        $this->entitiesResolver->single(new Urn($entityUrn))
            ->shouldBeCalled()
            ->willReturn($entity);

        $this->feedUserManager->getHasMadePostsFromCache($ownerGuid)
            ->shouldBeCalled()
            ->willReturn(false);

        $this->entitiesBuilder->single($ownerGuid)
            ->shouldBeCalled()
            ->willReturn($user);
        
        $this->feedUserManager->setHasMadePostsInCache($ownerGuid)
            ->shouldBeCalled();


        $this->feedUserManager->setUser($user)
            ->shouldBeCalled()
            ->willReturn($this->feedUserManager);

        $this->feedUserManager->hasMadePosts()
            ->shouldBeCalled()
            ->willReturn(true);

        $this->indexMock->index(Argument::that(function ($arg) {
            return $arg->getTags() === ['tag1'];
        }))
            ->shouldBeCalled()
            ->willReturn(true);

        $this->consume($event)->shouldBe(true);
    }

    public function it_should_index_an_activity_without_patched_tags_when_a_user_already_has_posted_when_stored_in_cache(
        User $user
    ) {
        $entityUrn = 'urn:activity:123';
        $ownerGuid = '234';

        $event = new EntitiesOpsEvent();
        $event->setOp(EntitiesOpsEvent::OP_CREATE)
            ->setEntityUrn($entityUrn);

        $entity = new Activity();
        $entity->owner_guid = $ownerGuid;
        $entity->setTags(['tag1']);

        $this->entitiesResolver->setOpts([
            'cache' => false
        ])
            ->shouldBeCalled()
            ->willReturn($this->entitiesResolver);

        $this->entitiesResolver->single(new Urn($entityUrn))
            ->shouldBeCalled()
            ->willReturn($entity);

        $this->feedUserManager->getHasMadePostsFromCache($ownerGuid)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->entitiesBuilder->single($ownerGuid)
            ->shouldNotBeCalled();
        
        $this->feedUserManager->setHasMadePostsInCache($ownerGuid)
            ->shouldNotBeCalled();

        $this->feedUserManager->setUser($user)
            ->shouldNotBeCalled();

        $this->feedUserManager->hasMadePosts()
            ->shouldNotBeCalled();

        $this->indexMock->index(Argument::that(function ($arg) {
            return $arg->getTags() === ['tag1'];
        }))
            ->shouldBeCalled()
            ->willReturn(true);

        $this->consume($event)->shouldBe(true);
    }

    public function it_should_index_an_activity_WITHOUT_patched_tags_when_a_user_has_posted_already(
        User $user
    ) {
        $entityUrn = 'urn:activity:123';
        $ownerGuid = '234';

        $event = new EntitiesOpsEvent();
        $event->setOp(EntitiesOpsEvent::OP_CREATE)
            ->setEntityUrn($entityUrn);

        $entity = new Activity();
        $entity->owner_guid = $ownerGuid;

        $this->entitiesResolver->setOpts([
            'cache' => false
        ])
            ->shouldBeCalled()
            ->willReturn($this->entitiesResolver);

        $this->entitiesResolver->single(new Urn($entityUrn))
            ->shouldBeCalled()
            ->willReturn($entity);

        $this->feedUserManager->getHasMadePostsFromCache($ownerGuid)
            ->shouldBeCalled()
            ->willReturn(false);

        $this->entitiesBuilder->single($ownerGuid)
            ->shouldBeCalled()
            ->willReturn($user);
        
        $this->feedUserManager->setHasMadePostsInCache($ownerGuid)
            ->shouldBeCalled();

        $this->feedUserManager->setUser($user)
            ->shouldBeCalled()
            ->willReturn($this->feedUserManager);

        $this->feedUserManager->hasMadePosts()
            ->shouldBeCalled()
            ->willReturn(true);

        $this->indexMock->index(Argument::that(function ($arg) {
            return $arg->getTags() === [];
        }))
            ->shouldBeCalled()
            ->willReturn(true);

        $this->consume($event)->shouldBe(true);
    }

    public function it_should_index_an_activity_WITHOUT_patched_tags_when_a_user_has_not_yet_posted_but_cannot_check_has_made_posts(
        User $user
    ) {
        $entityUrn = 'urn:activity:123';
        $ownerGuid = '234';

        $event = new EntitiesOpsEvent();
        $event->setOp(EntitiesOpsEvent::OP_CREATE)
            ->setEntityUrn($entityUrn);

        $entity = new Activity();
        $entity->owner_guid = $ownerGuid;

        $this->entitiesResolver->setOpts([
            'cache' => false
        ])
            ->shouldBeCalled()
            ->willReturn($this->entitiesResolver);

        $this->entitiesResolver->single(new Urn($entityUrn))
            ->shouldBeCalled()
            ->willReturn($entity);

        $this->feedUserManager->getHasMadePostsFromCache($ownerGuid)
            ->shouldBeCalled()
            ->willReturn(false);

        $this->entitiesBuilder->single($ownerGuid)
            ->shouldBeCalled()
            ->willReturn($user);
        
        $this->feedUserManager->setHasMadePostsInCache($ownerGuid)
            ->shouldNotBeCalled();

        $this->feedUserManager->setUser($user)
            ->shouldBeCalled()
            ->willReturn($this->feedUserManager);

        $this->feedUserManager->hasMadePosts()
            ->shouldBeCalled()
            ->willThrow(new \Exception());

        $this->indexMock->index(Argument::that(function ($arg) {
            return $arg->getTags() === [];
        }))
            ->shouldBeCalled()
            ->willReturn(true);

        $this->consume($event)->shouldBe(true);
    }
}
