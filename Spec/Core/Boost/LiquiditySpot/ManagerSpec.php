<?php

namespace Spec\Minds\Core\Boost\LiquiditySpot;

use Minds\Core\Boost\LiquiditySpot\Manager;
use Minds\Core\Boost\LiquiditySpot\Delegates;
use Brick\Math\BigDecimal;
use Minds\Core\Di\Di;
use Minds\Core\Counters;
use Minds\Core\Data\Redis;
use Minds\Core\Boost\Network\Boost;
use Minds\Core\Blockchain\LiquidityPositions;
use Minds\Core\Blockchain\LiquidityPositions\LiquidityPositionSummary;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Log\Logger;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ManagerSpec extends ObjectBehavior
{
    /** @var LiquidityPositions\Manager */
    protected $liquidityPositionsManager;

    /** @var Counters */
    protected $counters;

    /** @var Redis\Client */
    protected $redis;

    /** @var EntitiesBuilder */
    protected $entitiesBuilder;

    /** @var Logger */
    protected $logger;

    /** @var Delegates\AnalyticsDelegate */
    protected $analyticsDelegate;

    public function let(
        LiquidityPositions\Manager $liquidityPositionsManager = null,
        Counters $counters = null,
        Redis\Client $redis = null,
        EntitiesBuilder $entitiesBuilder = null,
        Logger $logger = null,
        Delegates\AnalyticsDelegate $analyticsDelegate = null
    ) {
        $this->beConstructedWith($liquidityPositionsManager, $counters, $redis, $entitiesBuilder, $logger, $analyticsDelegate);
        $this->liquidityPositionsManager = $liquidityPositionsManager;
        $this->counters = $counters;
        $this->redis = $redis;
        $this->entitiesBuilder = $entitiesBuilder;
        $this->logger = $logger;
        $this->analyticsDelegate = $analyticsDelegate;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }

    public function it_should_get_liquidity_spot()
    {
        $this->redis->get('boost:liquidity-spot')
            ->willReturn(serialize(
                (new Boost())
                    ->setEntityGuid('123')
            ));

        $user = new User();

        $this->entitiesBuilder->single('123')
            ->willReturn($user);

        $metricsKey = 'boost:liquidity-spot:' . strtotime('midnight');
        $this->counters->increment(0, $metricsKey)
            ->shouldBeCalled();
        $this->counters->increment('123', $metricsKey)
            ->shouldBeCalled();

        //

        $boost = $this->get();
        $boost->getEntity()
            ->shouldBe($user);
    }
}
