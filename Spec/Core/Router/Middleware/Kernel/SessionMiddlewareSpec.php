<?php

namespace Spec\Minds\Core\Router\Middleware\Kernel;

use Minds\Core\Router\Middleware\Kernel\SessionMiddleware;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class SessionMiddlewareSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(SessionMiddleware::class);
    }

    /**
     * Untestable due to the use of Session as static class
     */
}
