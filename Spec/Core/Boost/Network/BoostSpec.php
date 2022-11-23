<?php

namespace Spec\Minds\Core\Boost\Network;

use Minds\Core\Boost\Network\Boost;
use PhpSpec\ObjectBehavior;

class BoostSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(Boost::class);
    }

    public function it_should_generate_a_guid_if_one_does_not_exist()
    {
        $this->setGuid(null);
        $this->getGuid()->shouldMatch('/^\d{10,}/');
    }
}
