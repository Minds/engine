<?php

namespace Spec\Minds\Core\Feeds\Seen;

use Minds\Common\Repository\Response;
use Minds\Core\Data\Redis;
use Minds\Core\Feeds\Seen\Manager;
use Minds\Core\Feeds\Elastic\Manager as ElasticManager;
use Minds\Core\Feeds\FeedSyncEntity;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ManagerSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }

    // private function createSampleEntitiesResponseObject(array $entitiesGuidsToExclude = []): Response
    // {
    //     $entities = [];

    //     for ($i = 0; $i < 10; $i++) {
    //         if (in_array($i, $entitiesGuidsToExclude, true)) {
    //             continue;
    //         }
    //         $entities[] = (new FeedSyncEntity())->setGuid($i+1);
    //     }

    //     return new Response($entities);
    // }

    // public function it_should_retrieve_unseen_entities_with_no_pre_existing_cache_and_no_pseudo_id(
    //     ElasticManager $elasticManager,
    //     Redis\Client $redisClient
    // ) {
    //     $expectedResponse = $this->createSampleEntitiesResponseObject();

    //     $elasticManager
    //         ->getList(Argument::type("array"))
    //         ->shouldBeCalledOnce()
    //         ->willReturn($expectedResponse);

    //     $this->beConstructedWith($redisClient, $elasticManager);

    //     $this
    //         ->getUnseenTopEntities(new User(1), 10)
    //         ->shouldBeEqualTo($expectedResponse);
    // }

    // public function it_should_retrieve_unseen_entities_with_pre_existing_cache_and_no_pseudo_id(
    //     ElasticManager $elasticManager,
    //     Redis\Client $redisClient
    // ) {
    //     $expectedResponse = $this->createSampleEntitiesResponseObject([1,2]);

    //     $elasticManager
    //         ->getList(Argument::type("array"))
    //         ->shouldBeCalledOnce()
    //         ->willReturn($expectedResponse);

    //     $this->beConstructedWith($redisClient, $elasticManager);

    //     $this
    //         ->getUnseenTopEntities(new User(1), 10)
    //         ->shouldHaveCount($expectedResponse->count());
    // }

    // public function it_should_retrieve_unseen_entities_with_no_pre_existing_cache_and_pseudo_id(
    //     ElasticManager $elasticManager,
    //     Redis\Client $redisClient
    // ) {
    //     $_COOKIE["minds_pseudoid"] = "pseudoid";

    //     $expectedResponse = $this->createSampleEntitiesResponseObject();

    //     $elasticManager
    //         ->getList(Argument::type("array"))
    //         ->shouldBeCalledOnce()
    //         ->willReturn($expectedResponse);

    //     $this->beConstructedWith($redisClient, $elasticManager);

    //     $this
    //         ->getUnseenTopEntities(new User(1), 10)
    //         ->shouldHaveCount($expectedResponse->count());
    // }

    // public function it_should_retrieve_unseen_entities_with_pre_existing_cache_and_pseudo_id(
    //     ElasticManager $elasticManager,
    //     Redis\Client $redisClient
    // ) {
    //     $_COOKIE["minds_pseudoid"] = "pseudoid";

    //     $expectedResponse = $this->createSampleEntitiesResponseObject([1,2]);

    //     $elasticManager
    //         ->getList(Argument::type("array"))
    //         ->shouldBeCalledOnce()
    //         ->willReturn($expectedResponse);

    //     $this->beConstructedWith($redisClient, $elasticManager);

    //     $this
    //         ->getUnseenTopEntities(new User(1), 10)
    //         ->shouldHaveCount($expectedResponse->count());
    // }
}
