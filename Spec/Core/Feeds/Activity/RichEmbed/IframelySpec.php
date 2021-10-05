<?php

namespace Spec\Minds\Core\Feeds\Activity\RichEmbed;

use Minds\Core\Feeds\Activity\RichEmbed\Iframely;
use GuzzleHttp;
use Minds\Core\Config\Config;
use PhpSpec\ObjectBehavior;

class IframelySpec extends ObjectBehavior
{
    public function let(GuzzleHttp\Client $client, Config $config)
    {
        $this->beConstructedWith($client, $config);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Iframely::class);
    }
}
