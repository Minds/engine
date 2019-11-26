<?php

namespace Spec\Minds\Core\Boost\Network;

use Minds\Core\Boost\Network\CassandraRepository;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class CassandraRepositorySpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(CassandraRepository::class);
    }
}
