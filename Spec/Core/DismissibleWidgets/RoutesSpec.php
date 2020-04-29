<?php

namespace Spec\Minds\Core\DismissibleWidgets;

use Minds\Core\DismissibleWidgets\Routes;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class RoutesSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(Routes::class);
    }
}
