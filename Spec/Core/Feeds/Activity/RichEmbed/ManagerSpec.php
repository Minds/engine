<?php

namespace Spec\Minds\Core\Feeds\Activity\RichEmbed;

use Minds\Core\Config\Config;
use Minds\Core\Feeds\Activity\RichEmbed\Iframely;
use Minds\Core\Feeds\Activity\RichEmbed\Manager;
use PhpSpec\ObjectBehavior;

class ManagerSpec extends ObjectBehavior
{
    public function let(Iframely $iframely, Config $config)
    {
        $this->beConstructedWith($iframely, $config);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }
}
