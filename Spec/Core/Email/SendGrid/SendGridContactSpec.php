<?php

namespace Spec\Minds\Core\Email\SendGrid;

use Minds\Core\Email\SendGrid\SendGridContact;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class SendGridContactSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(SendGridContact::class);
    }
}
