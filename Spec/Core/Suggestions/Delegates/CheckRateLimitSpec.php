<?php

namespace Spec\Minds\Core\Suggestions\Delegates;

use Minds\Core\Data\cache\abstractCacher;
use Minds\Core\Data\cache\Redis;
use Minds\Core\Suggestions\Delegates\CheckRateLimit;
use PhpSpec\ObjectBehavior;

class CheckRateLimitSpec extends ObjectBehavior
{
    /** @var abstractCacher */
    private $cacher;

    function let(Redis $cacher)
    {
        $this->cacher = $cacher;

        $this->beConstructedWith($cacher);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType(CheckRateLimit::class);
    }

    function it_should_throw_an_exception_if_performing_a_check_but_userGuid_isnt_set()
    {

        $this->shouldThrow(new \Exception('userGuid must be provided'))->during('check', [null]);
    }

    function it_should_perform_a_check_and_return_true()
    {
        $this->cacher->get("subscriptions:user:123")
            ->shouldBeCalled()
            ->willReturn(false);

        $this->check(123)->shouldReturn(true);
    }

    function it_should_perform_a_check_and_return_false()
    {
        $this->cacher->get("subscriptions:user:123")
            ->shouldBeCalled()
            ->willReturn(41);

        // returns false because we're near the subscribe threshold
        $this->check(123)->shouldReturn(false);
    }


    function it_should_throw_an_exception_when_caching_the_response_but_userGuid_isnt_set()
    {
        $this->shouldThrow(new \Exception('userGuid must be provided'))->during('incrementCache', [null]);
    }

    function it_should_increment_the_cache()
    {
        $this->cacher->get("subscriptions:user:123")
            ->shouldBeCalled()
            ->willReturn(1);

        $this->cacher->set('subscriptions:user:123', 2, 300)
            ->shouldBeCalled();

        $this->incrementCache(123, 10);
    }
}
