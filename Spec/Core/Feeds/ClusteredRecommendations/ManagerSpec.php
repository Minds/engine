<?php
declare(strict_types=1);

namespace Spec\Minds\Core\Feeds\ClusteredRecommendations;

use Exception;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Experiments\Manager as ExperimentsManager;
use Minds\Core\Feeds\ClusteredRecommendations\Manager;
use Minds\Core\Feeds\ClusteredRecommendations\MySQLRepository;
use Minds\Core\Feeds\ClusteredRecommendations\RepositoryFactory;
use Minds\Core\Feeds\Elastic\ScoredGuid;
use Minds\Core\Feeds\FeedSyncEntity;
use Minds\Core\Feeds\Seen\Manager as SeenManager;
use Minds\Core\Hashtags\User\Manager as HashtagsManager;
use Minds\Core\Recommendations\UserRecommendationsCluster;
use Minds\Entities\Entity;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;
use Spec\Minds\Common\Traits\CommonMatchers;

class ManagerSpec extends ObjectBehavior
{
    use CommonMatchers;

    private Collaborator $entitiesBuilder;
    private Collaborator $userRecommendationsCluster;
    private Collaborator $seenManager;
    private Collaborator $repositoryFactory;
    private Collaborator $experimentsManager;
    private Collaborator $hashtagsManager;

    public function let(
        UserRecommendationsCluster $userRecommendationsCluster,
        SeenManager $seenManager,
        EntitiesBuilder $entitiesBuilder,
        RepositoryFactory $repositoryFactory,
        ExperimentsManager $experimentsManager,
        HashtagsManager $hashtagsManager
    ) {
        $this->entitiesBuilder = $entitiesBuilder;
        $this->userRecommendationsCluster = $userRecommendationsCluster;
        $this->seenManager = $seenManager;
        $this->repositoryFactory = $repositoryFactory;
        $this->experimentsManager = $experimentsManager;
        $this->hashtagsManager = $hashtagsManager;

        $this->beConstructedWith(
            null,
            $this->entitiesBuilder,
            $this->userRecommendationsCluster,
            $this->seenManager,
            $this->repositoryFactory,
            $this->experimentsManager,
            $this->hashtagsManager
        );
    }

    public function it_is_initializable(): void
    {
        $this->shouldBeAnInstanceOf(Manager::class);
    }

    /**
     * @param User $user
     * @param MySQLRepository $repository
     * @param Entity $entity
     * @return void
     * @throws Exception
     */
    public function it_should_get_list_of_recommendations_for_user(
        User $user,
        MySQLRepository $repository,
        Entity $entity
    ): void {
        $this->setUser($user);

        $this->hashtagsManager->setUser($user)
            ->shouldBeCalledOnce();
        $this->hashtagsManager->get(Argument::type('array'))
            ->shouldBeCalledOnce()
            ->willReturn([]);

        $this->seenManager->getIdentifier()
            ->shouldBeCalledOnce()
            ->willReturn("");

        $repository->getList(0, 12, [], true, "", [])
            ->shouldBeCalledOnce()
            ->willYield([
                (new ScoredGuid())
                    ->setGuid('123')
                    ->setType('activity')
                    ->setScore(1)
                    ->setOwnerGuid('123')
                    ->setTimestamp(0)
            ]);

        $repository->setUser($user);

        $this->repositoryFactory->getInstance(MySQLRepository::class)
            ->shouldBeCalledOnce()
            ->willReturn($repository);

        $entity->getGuid()
            ->shouldBeCalledOnce()
            ->willReturn("123");

        $entity->getOwnerGuid()
            ->shouldBeCalledOnce()
            ->willReturn("123");

        $entity->getUrn()
            ->shouldBeCalled()
            ->willReturn("");

        $this->entitiesBuilder->single('123')
            ->shouldBeCalledOnce()
            ->willReturn($entity);

        $this->getList(12, true)
            ->shouldContainAnInstanceOf(FeedSyncEntity::class);
    }
}
