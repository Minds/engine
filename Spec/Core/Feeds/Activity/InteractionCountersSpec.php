<?php

namespace Spec\Minds\Core\Feeds\Activity;

use Minds\Core\Data\cache\PsrWrapper;
use Minds\Core\Feeds\Activity\InteractionCounters;
use Minds\Core\Feeds\Elastic;
use Minds\Entities\Activity;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class InteractionCountersSpec extends ObjectBehavior
{
    /** @var PsrWrapper */
    protected $cache;

    /** @var Elastic\Manager */
    protected $feedsManager;
    
    public function let(PsrWrapper $cache, Elastic\Manager $feedsManager)
    {
        $this->beConstructedWith($cache, $feedsManager);
        $this->cache = $cache;
        $this->feedsManager = $feedsManager;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(InteractionCounters::class);
    }

    public function it_should_get_quote_counter(Activity $activity)
    {
        $this->feedsManager->getCount(Argument::any())
            ->willReturn(12);

        $this->cache->get('interactions:count:quotes:')
            ->willReturn(false);

        $this->cache->set('interactions:count:quotes:', 12, 2592000)
            ->shouldBeCalled();
        
        $this->setCounter('quotes')
            ->get($activity)
            ->shouldBe(12);
    }


    public function it_should_get_quote_counter_from_cache(Activity $activity)
    {
        $activity->getGuid()
            ->willReturn('123');
    
        $this->feedsManager->getCount(Argument::any())
            ->shouldNotBeCalled();

        $this->cache->get('interactions:count:quotes:123')
            ->shouldBeCalled()
            ->willReturn(24);
        
        $this->setCounter('quotes')
            ->get($activity)
            ->shouldBe(24);
    }
}
