<?php

namespace Spec\Minds\Core\FeedNotices;

use Minds\Core\FeedNotices\Manager;
use PhpSpec\ObjectBehavior;

class ManagerSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }
}
