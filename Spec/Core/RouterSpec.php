<?php

namespace Spec\Minds\Core;

use Minds\Core\Router;
use Minds\Core\Router\Dispatcher;
use Minds\Core\Router\PrePsr7\Fallback;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class RouterSpec extends ObjectBehavior
{
    /** @var Dispatcher */
    protected $dispatcher;

    /** @var Fallback */
    protected $fallback;

    public function let(
        Dispatcher $dispatcher,
        Fallback $fallback
    ) {
        $this->dispatcher = $dispatcher;
        $this->fallback = $fallback;

        $this->beConstructedWith($dispatcher, $fallback);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Router::class);
    }
}
