<?php

namespace Spec\Minds\Core\Feeds\UnseenTopFeed;

use Minds\Common\Repository\Response;
use Minds\Core\Config\Config;
use Minds\Core\Feeds\Elastic\Manager as ElasticSearchManager;
use Minds\Core\Feeds\UnseenTopFeed\Manager;
use PhpSpec\ObjectBehavior;

class ManagerSpec extends ObjectBehavior
{
    /** @var ElasticSearchManager */
    protected $elasticSearchManager;

    /** @var Config */
    protected $config;

    public function let(
        ElasticSearchManager $elasticSearchManager,
        Config $config
    ) {
        $this->elasticSearchManager = $elasticSearchManager;
        $this->config = $config;
        $this->beConstructedWith($elasticSearchManager, $config);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }

    public function it_should_get_list_defaulting_algorithm_to_top(Response $response)
    {
        $userGuid = '123';
        $limit = 12;

        $response->setPagingToken(null)
            ->shouldBeCalled();

        $this->config->get('unseen_top_algorithm')
            ->shouldBeCalled()
            ->willReturn(null);

        $this->elasticSearchManager->getList([
            'limit' => $limit,
            'type' => 'activity',
            'algorithm' => 'top',
            'subscriptions' => $userGuid,
            'single_owner_threshold' => 6,
            'period' => 'all', // legacy option
            'unseen' => true,
            'demoted' => true,
            'to_timestamp' => null,
            'from_timestamp' => null,
            'exclude' => null,
        ])
            ->shouldBeCalled()
            ->willReturn($response);

        $this->getList($userGuid, $limit)
            ->shouldBe($response);
    }

    public function it_should_get_list_with_algorithm_override(Response $response)
    {
        $userGuid = '123';
        $limit = 12;

        $response->setPagingToken(null)
            ->shouldBeCalled();

        $this->config->get('unseen_top_algorithm')
            ->shouldBeCalled()
            ->willReturn('latest');

        $this->elasticSearchManager->getList([
            'limit' => $limit,
            'type' => 'activity',
            'algorithm' => 'latest',
            'subscriptions' => $userGuid,
            'single_owner_threshold' => 6,
            'period' => 'all', // legacy option
            'unseen' => true,
            'demoted' => true,
            'to_timestamp' => null,
            'from_timestamp' => null,
            'exclude' => null,
        ])
            ->shouldBeCalled()
            ->willReturn($response);

        $this->getList($userGuid, $limit)
            ->shouldBe($response);
    }
}
