<?php

namespace Spec\Minds\Core\Pro\Assets;

use Exception;
use Minds\Core\Pro\Assets\Asset;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class AssetSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(Asset::class);
    }

    public function it_should_set_type()
    {
        $this
            ->setType(Asset::TYPES[0])
            ->getType()
            ->shouldReturn(Asset::TYPES[0]);
    }

    public function it_should_throw_if_type_is_invalid()
    {
        $this
            ->shouldThrow(Exception::class)
            ->duringSetType('-!__!@_#)!@#_)!@#@!_#)INVALID');
    }
}
