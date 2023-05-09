<?php
declare(strict_types=1);

namespace Spec\Minds\Core\Hashtags\WelcomeTag;

use Minds\Core\Hashtags\WelcomeTag\Manager;
use Minds\Core\Entities\Resolver;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Feeds\User\Manager as FeedsUserManager;
use Minds\Core\Log\Logger;
use Minds\Entities\Activity;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;

class ManagerSpec extends ObjectBehavior
{
    private Collaborator $resolver;
    private Collaborator $entitiesBuilder;
    private Collaborator $feedsUserManager;
    private Collaborator $logger;

    public function let(
        Resolver $resolver,
        EntitiesBuilder $entitiesBuilder,
        FeedsUserManager $feedsUserManager,
        Logger $logger
    ): void {
        $this->resolver = $resolver;
        $this->entitiesBuilder = $entitiesBuilder;
        $this->feedsUserManager = $feedsUserManager;
        $this->logger = $logger;

        $this->beConstructedWith($resolver, $entitiesBuilder, $feedsUserManager, $logger);
    }

    public function it_is_initializable(): void
    {
        $this->shouldHaveType(Manager::class);
    }

    public function it_should_append_welcome_tag_to_activity(Activity $activity): void
    {
        $activity->getTags()
            ->willReturn([]);

        $activity->setTags(['hellominds'])->shouldBeCalled();
        
        $this->append($activity)->shouldBeAnInstanceOf(Activity::class);
    }

    public function it_should_append_welcome_tag_to_activity_when_tags_already_exist(Activity $activity): void
    {
        $activity->getTags()
            ->willReturn(['mindshello']);

        $activity->setTags(['mindshello', 'hellominds'])
            ->shouldBeCalled();
        
        $this->append($activity)->shouldBeAnInstanceOf(Activity::class);
    }

    public function it_should_strip_welcome_tag_from_activity_when_there_is_a_single_tag(Activity $activity): void
    {
        $activity->getTags()
            ->willReturn(['hellominds']);

        $activity->setTags([])
            ->shouldBeCalled();

        $this->strip($activity)->shouldBeAnInstanceOf(Activity::class);
    }

    public function it_should_strip_nothing_from_activity_when_there_is_no_tags(Activity $activity): void
    {
        $activity->getTags()
            ->willReturn([]);

        $activity->setTags([])
            ->shouldNotBeCalled();

        $this->strip($activity)->shouldBeAnInstanceOf(Activity::class);
    }

    public function it_should_strip_nothing_from_activity_when_there_is_no_matching_tags(Activity $activity): void
    {
        $activity->getTags()
            ->willReturn(['mindshello', 'test']);

        $activity->setTags(['mindshello', 'test'])
            ->shouldNotBeCalled();

        $this->strip($activity)->shouldBeAnInstanceOf(Activity::class);
    }

    public function it_should_strip_welcome_tag_from_activity_when_there_are_multiple_tags(Activity $activity): void
    {
        $activity->getTags()
            ->willReturn(['mindshello', 'hellominds']);

        $activity->setTags(['mindshello'])
            ->shouldBeCalled();

        $this->strip($activity)->shouldBeAnInstanceOf(Activity::class);
    }

    public function it_should_check_if_welcome_tag_should_be_appended(Activity $activity, User $user, FeedsUserManager $feedsUserManager): void
    {
        $ownerGuid = '123';

        $activity->getOwnerGuid()
            ->shouldBeCalled()
            ->willReturn($ownerGuid);

        $feedsUserManager->getHasMadePostsFromCache($ownerGuid)
            ->shouldBeCalled()
            ->willReturn(false);

        $this->entitiesBuilder->single($ownerGuid)
            ->shouldBeCalled()
            ->willReturn($user);

        $feedsUserManager->setUser($user)
            ->shouldBeCalled()
            ->willReturn($feedsUserManager);

        $feedsUserManager->hasMadePosts()
            ->shouldBeCalled()
            ->willReturn(false);
        
        $feedsUserManager->setHasMadePostsInCache($ownerGuid)
            ->shouldBeCalled();
        
        $this->callOnWrappedObject('shouldAppend', [$activity])->shouldReturn(true);
    }

    public function it_should_check_if_user_has_made_activity_posts(User $user, FeedsUserManager $feedsUserManager): void
    {
        $ownerGuid = '123';

        $feedsUserManager->getHasMadePostsFromCache($ownerGuid)
            ->shouldBeCalled()
            ->willReturn(false);

        $this->entitiesBuilder->single($ownerGuid)
            ->shouldBeCalled()
            ->willReturn($user);

        $feedsUserManager->setUser($user)
            ->shouldBeCalled()
            ->willReturn($feedsUserManager);

        $feedsUserManager->hasMadePosts()
            ->shouldBeCalled()
            ->willReturn(true);

        $feedsUserManager->setHasMadePostsInCache($ownerGuid)
            ->shouldBeCalled();

        $this->hasMadeActivityPosts($ownerGuid)->shouldReturn(true);
    }

    public function it_should_check_if_user_has_made_activity_posts_when_cached(User $user, FeedsUserManager $feedsUserManager): void
    {
        $ownerGuid = '123';

        $feedsUserManager->getHasMadePostsFromCache($ownerGuid)
            ->shouldBeCalled()
            ->willReturn(true);

        $feedsUserManager->hasMadePosts()
            ->shouldNotBeCalled();

        $this->hasMadeActivityPosts($ownerGuid)->shouldReturn(true);
    }
}
