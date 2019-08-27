<?php

namespace Spec\Minds\Core\Faq;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ManagerSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType('Minds\Core\Faq\Manager');
    }

    public function it_should_read_the_csv()
    {
        $this->get()->shouldBeArray();
        $this->get()->shouldHaveCount(17);
    }
}
