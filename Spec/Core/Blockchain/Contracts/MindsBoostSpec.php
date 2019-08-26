<?php

namespace Spec\Minds\Core\Blockchain\Contracts;

use Minds\Core\Blockchain\Contracts\MindsBoost;
use PhpSpec\ObjectBehavior;

class MindsBoostSpec extends ObjectBehavior
{
    public function let()
    {
        $this->beConstructedWith('0x123');
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(MindsBoost::class);
    }

    public function it_should_get_the_abi()
    {
        $this->getABI()->shouldBeArray();
    }
}
