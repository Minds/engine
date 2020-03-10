<?php

namespace Spec\Minds\Core\Email\SendGrid;

use Minds\Core\Email\SendGrid\Webhooks;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class WebhooksSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType(Webhooks::class);
    }
}
