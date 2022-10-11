<?php

declare(strict_types=1);

namespace Spec\Minds\Core\Feeds\Supermind;

use Exception;
use Minds\Common\Repository\Response;
use Minds\Core\Feeds\Elastic\Manager as ElasticSearchManager;
use Minds\Core\Feeds\Supermind\Manager;
use PhpSpec\ObjectBehavior;

class ManagerSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }

    /**
     * @param ElasticSearchManager $elasticSearchManager
     * @param Response $response
     * @return void
     * @throws Exception
     */
    public function it_should_get_supermind_activities(
        ElasticSearchManager $elasticSearchManager,
        Response             $response
    ): void {
        $elasticSearchManager
            ->getList([
                'limit' => 12,
                'type' => 'activity',
                'algorithm' => 'latest',
                'single_owner_threshold' => 0,
                'period' => 'all', // legacy option
                'to_timestamp' => null,
                'from_timestamp' => null,
                'supermind' => true
            ])
            ->shouldBeCalled()
            ->willReturn($response);

        $this->beConstructedWith($elasticSearchManager);

        $this->getSupermindActivities()
            ->shouldBe($response);
    }
}
