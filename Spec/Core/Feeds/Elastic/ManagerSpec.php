<?php

namespace Spec\Minds\Core\Feeds\Elastic;

use Minds\Common\Repository\Response;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Feeds\Elastic\Manager;
use Minds\Core\Feeds\Elastic\Repository;
use Minds\Core\Feeds\Elastic\ScoredGuid;
use Minds\Core\Feeds\Seen\Manager as SeenManager;
use Minds\Core\Search\Search;
use Minds\Entities\Entity;
use PhpSpec\Exception\Example\FailureException;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ManagerSpec extends ObjectBehavior
{
    /** @var Repository */
    protected $repository;

    /** @var EntitiesBuilder */
    protected $entitiesBuilder;

    /** @var Search */
    protected $search;

    /** @var SeenManager */
    protected $seenManager;

    public function let(
        Repository $repository,
        EntitiesBuilder $entitiesBuilder,
        Search $search,
        SeenManager $seenManager
    ) {
        $this->repository = $repository;
        $this->entitiesBuilder = $entitiesBuilder;
        $this->search = $search;
        $this->seenManager = $seenManager;
        $this->beConstructedWith($repository, $entitiesBuilder, null, $search, $seenManager);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }

    public function it_should_get_list(
        ScoredGuid $scoredGuid1,
        ScoredGuid $scoredGuid2,
        Entity $entity1,
        Entity $entity2
    ) {
        $this->mockScoredEntities($scoredGuid1, $scoredGuid2, $entity1, $entity2);

        $this->repository->getList(Argument::withEntry('cache_key', 'phpspec'))
            ->shouldBeCalled()
            ->willReturn([$scoredGuid1, $scoredGuid2]);

        $response = $this
            ->getList([
                'cache_key' => 'phpspec',
            ]);
        $response[0]->getUrn()
            ->shouldBe('urn:image:500');
        $response[1]->getUrn()
            ->shouldBe('urn:activity:5001');
    }

    public function it_should_get_list_by_query(
        ScoredGuid $scoredGuid1,
        ScoredGuid $scoredGuid2,
        Entity $entity1,
        Entity $entity2
    ) {
        $this->mockScoredEntities($scoredGuid1, $scoredGuid2, $entity1, $entity2);

        $this->repository->getList(Argument::withEntry('query', 'activity with hashtags'))
            ->shouldBeCalled()
            ->willReturn([$scoredGuid1, $scoredGuid2]);

        $response = $this
            ->getList([
                'query' => 'Activity with #hashtags',
            ]);
        
        $response[0]->getUrn()
            ->shouldBe('urn:image:500');
        $response[1]->getUrn()
            ->shouldBe('urn:activity:5001');
    }

    public function it_should_get_unseen_list(
        ScoredGuid $scoredGuid1,
        ScoredGuid $scoredGuid2,
        Entity $entity1,
        Entity $entity2,
    ) {
        $this->mockScoredEntities($scoredGuid1, $scoredGuid2, $entity1, $entity2);

        $this->seenManager->listSeenEntities()->shouldBeCalledOnce()->willReturn(['fakeSeenGuid']);

        $this->repository->getList(Argument::withEntry('exclude', ['fakeSeenGuid']))
            ->shouldBeCalled()
            ->willReturn([$scoredGuid1, $scoredGuid2]);

        $response = $this
            ->getList([
                'unseen' => true,
            ]);

        $response[0]->getUrn()
            ->shouldBe('urn:image:500');
        $response[1]->getUrn()
            ->shouldBe('urn:activity:5001');
    }

    public function it_should_get_unseen_list_with_additional_exclude(
        ScoredGuid $scoredGuid1,
        ScoredGuid $scoredGuid2,
        Entity $entity1,
        Entity $entity2,
    ) {
        $this->mockScoredEntities($scoredGuid1, $scoredGuid2, $entity1, $entity2);

        $this->seenManager->listSeenEntities()->shouldBeCalledOnce()->willReturn(['fakeSeenGuid']);

        $this->repository->getList(Argument::withEntry('exclude', ['fakeExcludedGuid', 'fakeSeenGuid']))
            ->shouldBeCalled()
            ->willReturn([$scoredGuid1, $scoredGuid2]);

        $response = $this
            ->getList([
                'unseen' => true,
                'exclude' => ['fakeExcludedGuid']
            ]);

        $response[0]->getUrn()
            ->shouldBe('urn:image:500');
        $response[1]->getUrn()
            ->shouldBe('urn:activity:5001');
    }


    public function it_should_not_get_unseen_if_not_given(
        ScoredGuid $scoredGuid1,
        ScoredGuid $scoredGuid2,
        Entity $entity1,
        Entity $entity2,
    ) {
        $this->mockScoredEntities($scoredGuid1, $scoredGuid2, $entity1, $entity2);

        $this->seenManager->listSeenEntities()->shouldNotBeCalled();

        $this->repository->getList(Argument::any())
            ->shouldBeCalled()
            ->willReturn([$scoredGuid1, $scoredGuid2]);

        $response = $this
            ->getList([
                'unseen' => false,
            ]);

        $response[0]->getUrn()
            ->shouldBe('urn:image:500');
        $response[1]->getUrn()
            ->shouldBe('urn:activity:5001');
    }

    public function getMatchers(): array
    {
        $matchers = [];

        $matchers['beAResponse'] = function ($subject, $elements = null) {
            if (!($subject instanceof Response)) {
                throw new FailureException("Subject should be a Response");
            }

            if ($elements !== null && $elements !== $subject->toArray()) {
                throw new FailureException("Subject elements don't match");
            }

            return true;
        };

        return $matchers;
    }

    private function mockScoredEntities($scoredGuid1, $scoredGuid2, $entity1, $entity2)
    {
        $scoredGuid1->getGuid()
            ->shouldBeCalled()
            ->willReturn(5000);

        $scoredGuid1->getScore()
            ->shouldBeCalled()
            ->willReturn(500);

        $scoredGuid1->getOwnerGuid()
            ->shouldBeCalled()
            ->willReturn(1000);

        $scoredGuid1->getTimestamp()
            ->shouldBeCalled()
            ->willReturn(2);

        $scoredGuid1->getType()
            ->shouldBeCalled()
            ->willReturn('object:image');

        $entity1->getGUID()
            ->shouldBeCalled()
            ->willReturn(5000);

        $entity1->getOwnerGUID()
            ->shouldBeCalled()
            ->willReturn(1000);

        $entity1->getUrn()
            ->shouldBeCalled()
            ->willReturn("urn:image:500");

        $scoredGuid2->getGuid()
            ->shouldBeCalled()
            ->willReturn(5001);

        $scoredGuid2->getScore()
            ->shouldBeCalled()
            ->willReturn(800);

        $scoredGuid2->getOwnerGuid()
            ->shouldBeCalled()
            ->willReturn(1001);

        $scoredGuid2->getTimestamp()
            ->shouldBeCalled()
            ->willReturn(1);

        $scoredGuid2->getType()
            ->shouldBeCalled()
            ->willReturn('activity');

        $entity2->getGUID()
            ->shouldBeCalled()
            ->willReturn(5001);

        $entity2->getOwnerGUID()
            ->shouldBeCalled()
            ->willReturn(1001);

        $entity2->getUrn()
            ->shouldBeCalled()
            ->willReturn("urn:activity:5001");
        
        $this->entitiesBuilder->get(['guids' => [5000, 5001]])
            ->shouldBeCalled()
            ->willReturn([$entity1, $entity2]);
    }
}
