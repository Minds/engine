<?php

namespace Spec\Minds\Core\Referrals;

use Minds\Core\Referrals\Repository;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class RepositorySpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType(Repository::class);
    }
}
