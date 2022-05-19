<?php

namespace Spec\Minds\Core\Feeds\UnseenTopFeed;

use Minds\Common\Repository\Response;
use Minds\Core\Feeds\Elastic\Manager as ElasticSearchManager;
use Minds\Core\Feeds\UnseenTopFeed\Manager;
use PhpSpec\ObjectBehavior;

class ManagerSpec extends ObjectBehavior
{
    /** @var ElasticSearchManager */
    protected $elasticSearchManager;

    public function let(
        ElasticSearchManager $elasticSearchManager
    ) {
        $this->elasticSearchManager = $elasticSearchManager;
        $this->beConstructedWith($elasticSearchManager);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }

    public function it_should_get_list(Response $response)
    {
        $userGuid = '123';
        $limit = 12;

        $response->setPagingToken(null)
            ->shouldBeCalled();

        $this->elasticSearchManager->getList([
            'limit' => $limit,
            'type' => 'activity',
            'algorithm' => 'top',
            'subscriptions' => $userGuid,
            'single_owner_threshold' => 6,
            'period' => 'all', // legacy option
            'unseen' => true,
            'demoted' => true
        ])
            ->shouldBeCalled()
            ->willReturn($response);

        $this->getList($userGuid, $limit)
            ->shouldBe($response);
    }
}
