<?php
declare(strict_types=1);

namespace Spec\Minds\Core\Feeds\GraphQL\TagRecommendations;

use Minds\Common\Access;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Feeds\Elastic\V2\Enums\SeenEntitiesFilterStrategyEnum;
use Minds\Core\Feeds\Elastic\V2\Manager as FeedsManager;
use Minds\Core\Feeds\Elastic\V2\QueryOpts;
use Minds\Core\Feeds\GraphQL\TagRecommendations\Manager;
use Minds\Core\Feeds\GraphQL\Types\ActivityEdge;
use Minds\Core\Feeds\GraphQL\Types\FeedExploreTagEdge;
use Minds\Core\Feeds\GraphQL\Types\FeedHeaderEdge;
use Minds\Core\Hashtags\User\Manager as UserHashtagsManager;
use Minds\Core\Log\Logger;
use Minds\Core\Search\Enums\SearchMediaTypeEnum;
use PhpSpec\ObjectBehavior;
use Minds\Core\Search\Search;
use Minds\Entities\Activity;
use Minds\Entities\Group;
use Minds\Entities\User;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;
use Spec\Minds\Common\Traits\CommonMatchers;

class ManagerSpec extends ObjectBehavior
{
    use CommonMatchers;

    private Collaborator $feedsManager;
    private Collaborator $entitiesBuilder;
    private Collaborator $search;
    private Collaborator $userHashtagsManager;
    private Collaborator $logger;

    public function let(
        FeedsManager $feedsManager,
        EntitiesBuilder $entitiesBuilder,
        Search $search,
        UserHashtagsManager $userHashtagsManager,
        Logger $logger
    ) {
        $this->feedsManager = $feedsManager;
        $this->entitiesBuilder = $entitiesBuilder;
        $this->search = $search;
        $this->userHashtagsManager = $userHashtagsManager;
        $this->logger = $logger;

        $this->beConstructedWith(
            $this->feedsManager,
            $this->entitiesBuilder,
            $this->search,
            $this->userHashtagsManager,
            $this->logger
        );
    }

    public function it_is_initializable(): void
    {
        $this->shouldBeAnInstanceOf(Manager::class);
    }

    public function it_should_prepend_recs_with_a_given_tag(
        User $user,
    ): void {
        $activity1 = new Activity();
        $activity2 = new Activity();

        $group1 = new Group();
        $group2 = new Group();

        $edges = [];
        $tag = 'minds';
        $recGroupGuid1 = '1234567890123450';
        $recGroupGuid2 = '1234567890123451';

        $this->userHashtagsManager->setUser($user)
            ->shouldNotBeCalled();
       
        $this->feedsManager->getTop(queryOpts: new QueryOpts(
            limit: 3,
            query: $tag,
            accessId: Access::PUBLIC,
            mediaTypeEnum: SearchMediaTypeEnum::toMediaTypeEnum(SearchMediaTypeEnum::ALL),
            nsfw: [],
            seenEntitiesFilterStrategy: SeenEntitiesFilterStrategyEnum::DEMOTE,
        ))
            ->shouldBeCalled()
            ->willYield([ $activity1, $activity2 ]);

        
        $this->search->suggest('group', $tag, 4)
            ->shouldBeCalled()
            ->willReturn([
                ['guid' => $recGroupGuid1],
                ['guid' => $recGroupGuid2]
            ]);

        $this->entitiesBuilder->single($recGroupGuid1)
            ->shouldBeCalled()
            ->willReturn($group1);

        $this->entitiesBuilder->single($recGroupGuid2)
            ->shouldBeCalled()
            ->willReturn($group2);

        $this->logger->error(Argument::any())
            ->shouldNotBeCalled();

        $result = $this->prepend(
            $edges,
            $user,
            'minds',
            ''
        );

        $result->shouldContainValueLike(new FeedHeaderEdge("#$tag trending", ''));
        $result->shouldContainValueLike(new ActivityEdge($activity1, '', false));
        $result->shouldContainValueLike(new ActivityEdge($activity2, '', false));
        $result->shouldContainValueLike(new FeedExploreTagEdge($tag, ''));
        $result->shouldContainValueLike(new FeedHeaderEdge("Based on your interests", ''));
    }

    public function it_should_prepend_recs_with_a_random_user_tag_when_one_is_not_supplied(
        User $user,
    ): void {
        $activity1 = new Activity();
        $activity2 = new Activity();

        $group1 = new Group();
        $group2 = new Group();

        $edges = [];
        $tag = 'minds';
        $recGroupGuid1 = '1234567890123450';
        $recGroupGuid2 = '1234567890123451';

        $this->userHashtagsManager->setUser($user)
            ->shouldBeCalled()
            ->willReturn($this->userHashtagsManager);

        $this->userHashtagsManager->getRandomUserSelectedTag()
            ->shouldBeCalled()
            ->willReturn($tag);
       
        $this->feedsManager->getTop(queryOpts: new QueryOpts(
            limit: 3,
            query: $tag,
            accessId: Access::PUBLIC,
            mediaTypeEnum: SearchMediaTypeEnum::toMediaTypeEnum(SearchMediaTypeEnum::ALL),
            nsfw: [],
            seenEntitiesFilterStrategy: SeenEntitiesFilterStrategyEnum::DEMOTE,
        ))
            ->shouldBeCalled()
            ->willYield([ $activity1, $activity2 ]);

        
        $this->search->suggest('group', $tag, 4)
            ->shouldBeCalled()
            ->willReturn([
                ['guid' => $recGroupGuid1],
                ['guid' => $recGroupGuid2]
            ]);

        $this->entitiesBuilder->single($recGroupGuid1)
            ->shouldBeCalled()
            ->willReturn($group1);

        $this->entitiesBuilder->single($recGroupGuid2)
            ->shouldBeCalled()
            ->willReturn($group2);

        $this->logger->error(Argument::any())
            ->shouldNotBeCalled();

        $result = $this->prepend(
            $edges,
            $user,
            null,
            ''
        );

        $result->shouldContainValueLike(new FeedHeaderEdge("#$tag trending", ''));
        $result->shouldContainValueLike(new ActivityEdge($activity1, '', false));
        $result->shouldContainValueLike(new ActivityEdge($activity2, '', false));
        $result->shouldContainValueLike(new FeedExploreTagEdge($tag, ''));
        $result->shouldContainValueLike(new FeedHeaderEdge("Based on your interests", ''));
    }

    public function it_should_not_prepend_recs_when_no_activities_are_found(
        User $user
    ) {
        $edges = [];
        $tag = 'minds';

        $this->userHashtagsManager->setUser($user)
            ->shouldBeCalled()
            ->willReturn($this->userHashtagsManager);

        $this->userHashtagsManager->getRandomUserSelectedTag()
            ->shouldBeCalled()
            ->willReturn($tag);
       
        $this->feedsManager->getTop(queryOpts: new QueryOpts(
            limit: 3,
            query: $tag,
            accessId: Access::PUBLIC,
            mediaTypeEnum: SearchMediaTypeEnum::toMediaTypeEnum(SearchMediaTypeEnum::ALL),
            nsfw: [],
            seenEntitiesFilterStrategy: SeenEntitiesFilterStrategyEnum::DEMOTE,
        ))
            ->shouldBeCalled()
            ->willYield([]);

        $this->logger->error(Argument::any())
            ->shouldNotBeCalled();

        $result = $this->prepend(
            $edges,
            $user,
            null,
            ''
        );

        $result->shouldHaveALengthOf(0);
    }

    public function it_should_not_modify_edges_on_exception_thrown(
        User $user
    ) {
        $edges = [];
        $tag = 'minds';
        $exception = new \Exception('Error thrown');

        $this->userHashtagsManager->setUser($user)
            ->shouldBeCalled()
            ->willReturn($this->userHashtagsManager);

        $this->userHashtagsManager->getRandomUserSelectedTag()
            ->shouldBeCalled()
            ->willReturn($tag);
        
        $this->feedsManager->getTop(queryOpts: new QueryOpts(
            limit: 3,
            query: $tag,
            accessId: Access::PUBLIC,
            mediaTypeEnum: SearchMediaTypeEnum::toMediaTypeEnum(SearchMediaTypeEnum::ALL),
            nsfw: [],
            seenEntitiesFilterStrategy: SeenEntitiesFilterStrategyEnum::DEMOTE,
        ))
            ->shouldBeCalled()
            ->willThrow($exception);

        $this->logger->error($exception)
            ->shouldBeCalled();

        $result = $this->prepend(
            $edges,
            $user,
            null,
            ''
        );

        $result->shouldHaveALengthOf(0);
    }
}
