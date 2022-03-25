<?php

namespace Spec\Minds\Core\DismissibleNotices;

use Minds\Core\DismissibleNotices\Routes;
use PhpSpec\ObjectBehavior;

class RoutesSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(Routes::class);
    }
}
