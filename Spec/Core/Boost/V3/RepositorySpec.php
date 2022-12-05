<?php

namespace Spec\Minds\Core\Boost\V3;

use Minds\Core\Boost\V3\Repository;
use PhpSpec\ObjectBehavior;

class RepositorySpec extends ObjectBehavior
{
    public function it_is_initializable(): void
    {
        $this->shouldBeAnInstanceOf(Repository::class);
    }
}
