<?php

namespace Spec\Minds\Core\Email\SendGrid\Lists;

use Minds\Core\Email\SendGrid\Lists\MonetizedUsersList;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class MonetizedUsersListSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(MonetizedUsersList::class);
    }
}
