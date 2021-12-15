<?php

namespace Spec\Minds\Core\Recommendations;

use Minds\Core\Recommendations\Manager;
use PhpSpec\ObjectBehavior;

class ManagerSpec extends ObjectBehavior
{
    public function it_is_initializable(): void
    {
        $this->shouldHaveType(Manager::class);
    }

    public function it_should_return_recommendations(): void
    {
        $this->getRecommendations("feed-sidebar");
    }
}
