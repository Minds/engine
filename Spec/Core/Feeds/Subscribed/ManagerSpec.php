<?php

namespace Spec\Minds\Core\Feeds\Subscribed;

use Minds\Core\Feeds\Elastic\Manager as ElasticManager;
use Minds\Core\Feeds\Subscribed\Manager;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ManagerSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }

    public function it_should_retrieve_unseen_entities_with_no_pre_existing_cache_and_no_pseudo_id(
        ElasticManager $elasticManager,
    ) {
        $expectedResponse = 1;

        $elasticManager
            ->getCount(Argument::type("array"))
            ->shouldBeCalledOnce()
            ->willReturn($expectedResponse);

        $this->beConstructedWith($elasticManager);

        $this
            ->getLatestCount(new User(1), 1650033742800)
            ->shouldBeEqualTo($expectedResponse);
    }
}
