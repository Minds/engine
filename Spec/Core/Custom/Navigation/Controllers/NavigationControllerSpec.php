<?php

namespace Spec\Minds\Core\Custom\Navigation\Controllers;

use Minds\Core\Custom\Navigation\Controllers\NavigationController;
use PhpSpec\ObjectBehavior;

class NavigationControllerSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(NavigationController::class);
    }
}
