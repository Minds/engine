<?php

namespace Spec\Minds\Core\SocialCompass;

use Minds\Core\SocialCompass\Controller;
use PhpSpec\ObjectBehavior;

class ControllerSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(Controller::class);
    }
}
