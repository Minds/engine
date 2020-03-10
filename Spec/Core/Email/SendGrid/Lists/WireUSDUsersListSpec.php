<?php

namespace Spec\Minds\Core\Email\SendGrid\Lists;

use Minds\Core\Email\SendGrid\Lists\WireUSDUsersList;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class WireUSDUsersListSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(WireUSDUsersList::class);
    }
}
