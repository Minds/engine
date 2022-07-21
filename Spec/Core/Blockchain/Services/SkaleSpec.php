<?php

namespace Spec\Minds\Core\Blockchain\Services;

use PhpSpec\ObjectBehavior;

class SkaleSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType('Minds\Core\Blockchain\Services\Ethereum');
    }
}
