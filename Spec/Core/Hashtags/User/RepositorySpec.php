<?php

namespace Spec\Minds\Core\Hashtags\User;

use Minds\Core\Hashtags\User\Repository;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class RepositorySpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(Repository::class);
    }
}
