<?php

namespace Spec\Minds\Core\Feeds\UnseenTopFeed;

use Minds\Common\Repository\Response;
use Minds\Core\Data\cache\Redis;
use Minds\Core\Feeds\Elastic\Manager as ElasticManager;
use Minds\Core\Feeds\FeedSyncEntity;
use Minds\Core\Feeds\UnseenTopFeed\Manager;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ManagerSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }

    private function createSampleEntitiesResponseObject(array $entitiesGuidsToExclude = []): Response
    {
        $entities = [];

        for ($i = 0; $i < 10; $i++) {
            if (in_array($i, $entitiesGuidsToExclude, true)) {
                continue;
            }
            $entities[] = (new FeedSyncEntity())->setGuid($i+1);
        }

        return new Response($entities);
    }

    public function it_should_retrieve_unseen_entities_with_no_pre_existing_cache_and_no_pseudo_id(
        ElasticManager $elasticManager,
        Redis $redisClient
    ) {
        $redisClient
            ->get(Argument::any())
            ->willReturn(false);

        $redisClient
            ->set(Argument::any(), Argument::any(), Argument::any())
            ->shouldBeCalledOnce();

        $expectedResponse = $this->createSampleEntitiesResponseObject();

        $elasticManager
            ->getList(Argument::type("array"))
            ->shouldBeCalledOnce()
            ->willReturn($expectedResponse);

        $this->beConstructedWith($redisClient, $elasticManager);

        $this
            ->getUnseenTopEntities(10)
            ->shouldBeEqualTo($expectedResponse);
    }

    public function it_should_retrieve_unseen_entities_with_pre_existing_cache_and_no_pseudo_id(
        ElasticManager $elasticManager,
        Redis $redisClient
    ) {
        $redisClient
            ->get(Argument::any())
            ->willReturn([1,2]);

        $redisClient
            ->set(Argument::any(), Argument::any(), Argument::any())
            ->shouldBeCalledOnce();

        $expectedResponse = $this->createSampleEntitiesResponseObject([1,2]);

        $elasticManager
            ->getList(Argument::type("array"))
            ->shouldBeCalledOnce()
            ->willReturn($expectedResponse);

        $this->beConstructedWith($redisClient, $elasticManager);

        $this
            ->getUnseenTopEntities(10)
            ->shouldHaveCount($expectedResponse->count());
    }

    public function it_should_retrieve_unseen_entities_with_no_pre_existing_cache_and_pseudo_id(
        ElasticManager $elasticManager,
        Redis $redisClient
    ) {
        $_COOKIE["minds_pseudoid"] = "pseudoid";
        $redisClient
            ->get(Argument::any())
            ->willReturn();

        $redisClient
            ->set(Argument::any(), Argument::any(), Argument::any())
            ->shouldBeCalledOnce();

        $expectedResponse = $this->createSampleEntitiesResponseObject();

        $elasticManager
            ->getList(Argument::type("array"))
            ->shouldBeCalledOnce()
            ->willReturn($expectedResponse);

        $this->beConstructedWith($redisClient, $elasticManager);

        $this
            ->getUnseenTopEntities(10)
            ->shouldHaveCount($expectedResponse->count());
    }

    public function it_should_retrieve_unseen_entities_with_pre_existing_cache_and_pseudo_id(
        ElasticManager $elasticManager,
        Redis $redisClient
    ) {
        $_COOKIE["minds_pseudoid"] = "pseudoid";
        $redisClient
            ->get(Argument::any())
            ->willReturn([1,2]);

        $redisClient
            ->set(Argument::any(), Argument::any(), Argument::any())
            ->shouldBeCalledOnce();

        $expectedResponse = $this->createSampleEntitiesResponseObject([1,2]);

        $elasticManager
            ->getList(Argument::type("array"))
            ->shouldBeCalledOnce()
            ->willReturn($expectedResponse);

        $this->beConstructedWith($redisClient, $elasticManager);

        $this
            ->getUnseenTopEntities(10)
            ->shouldHaveCount($expectedResponse->count());
    }
}
