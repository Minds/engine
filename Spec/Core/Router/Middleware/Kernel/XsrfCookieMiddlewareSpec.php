<?php

namespace Spec\Minds\Core\Router\Middleware\Kernel;

use Minds\Core\Router\Middleware\Kernel\XsrfCookieMiddleware;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class XsrfCookieMiddlewareSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(XsrfCookieMiddleware::class);
    }

    /**
     * Untestable due to the use of XSRF as static class
     */
}
