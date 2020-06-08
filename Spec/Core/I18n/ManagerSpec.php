<?php

namespace Spec\Minds\Core\I18n;

use Minds\Core\I18n\Manager;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ManagerSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }
}
