<?php

namespace Spec\Minds\Core\Security\Block;

use Minds\Core\Security\Block\Repository;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class RepositorySpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(Repository::class);
    }
}
