<?php

namespace Spec\Minds\Core\Boost\V3\Ranking;

use Minds\Core\Boost\V3\Ranking\Repository;
use PhpSpec\ObjectBehavior;

class RepositorySpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType(Repository::class);
    }
}
