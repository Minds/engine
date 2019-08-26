<?php

namespace Spec\Minds\Core\ThirdPartyNetworks;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class FactorySpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType('Minds\Core\ThirdPartyNetworks\Factory');
    }
}
