<?php
declare(strict_types=1);

namespace Spec\Minds\Core\Hashtags\WelcomeTag;

use Minds\Core\Hashtags\WelcomeTag\Manager;
use Minds\Core\Entities\Resolver;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Feeds\User\Manager as FeedsUserManager;
use Minds\Core\Log\Logger;
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

    public function it_should_append_welcome_tag_to_activity(): void
    {
        $this->append([])->shouldBe(['hellominds']);
    }

    public function it_should_append_welcome_tag_to_activity_when_tags_already_exist(): void
    {
        $this->append(['mindshello'])->shouldBe(['mindshello', 'hellominds']);
    }

    public function it_should_NOT_append_welcome_tag_to_activity_when_welcome_tag_already_exist(): void
    {
        $this->append(['mindshello', 'hellominds'])->shouldBe(['mindshello', 'hellominds']);
    }

    public function it_should_remove_welcome_tag_from_activity_when_there_is_a_single_tag(): void
    {
        $this->remove(['hellominds'])->shouldBe([]);
    }

    public function it_should_remove_welcome_tag_from_activity_when_there_is_a_single_tag_in_varied_case(): void
    {
        $this->remove(['heLlOmInDS'])->shouldBe([]);
    }

    public function it_should_remove_nothing_from_activity_when_there_is_no_tags(): void
    {
        $this->remove([])->shouldBe([]);
    }

    public function it_should_remove_nothing_from_activity_when_there_is_no_matching_tags(): void
    {
        $this->remove(['mindshello', 'test'])->shouldBe(['mindshello', 'test']);
    }

    public function it_should_remove_welcome_tag_from_activity_when_there_are_multiple_tags(): void
    {
        $this->remove(['mindshello', 'hellominds'])->shouldBe(['mindshello']);
    }

    public function it_should_remove_welcome_tag_from_activity_when_there_are_multiple_welcome_tags(): void
    {
        $this->remove(['hellominds', 'mindshello', 'hellominds'])->shouldBe(['mindshello']);
    }

    public function it_should_check_if_a_tag_should_be_appended(User $user, FeedsUserManager $feedsUserManager): void
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
            ->willReturn(false);

        $feedsUserManager->setHasMadePostsInCache($ownerGuid)
            ->shouldBeCalled();

        $this->callOnWrappedObject('shouldAppend', [$ownerGuid])->shouldReturn(true);
    }

    public function it_should_check_if_a_tag_should_not_be_appended(User $user, FeedsUserManager $feedsUserManager): void
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

        $this->callOnWrappedObject('shouldAppend', [$ownerGuid])->shouldReturn(false);
    }

    public function it_should_check_if_a_tag_should_not_be_appended_when_cached(FeedsUserManager $feedsUserManager): void
    {
        $ownerGuid = '123';

        $feedsUserManager->getHasMadePostsFromCache($ownerGuid)
            ->shouldBeCalled()
            ->willReturn(true);

        $feedsUserManager->hasMadePosts()
            ->shouldNotBeCalled();

        $this->callOnWrappedObject('shouldAppend', [$ownerGuid])->shouldReturn(false);
    }
}
