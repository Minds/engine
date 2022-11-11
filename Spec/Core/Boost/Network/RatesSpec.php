<?php

namespace Spec\Minds\Core\Boost\Network;

use Minds\Core\Boost\Network\Rates;
use Minds\Core\Config\Config;
use PhpSpec\ObjectBehavior;

class RatesSpec extends ObjectBehavior
{
    /** @var Config */
    private $config;

    public function let(
        Config $config,
    ) {
        $this->config = $config;

        $this->config->get('boost')
            ->shouldBeCalled()
            ->willReturn([
                'network' => [
                    'cash_impression_rate' => 3000,
                    'token_impression_rate' => 2000
                ]
            ]);

        $this->beConstructedWith($config);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Rates::class);
    }

    public function it_should_get_usd_rate()
    {
        $this->getUsdRate()->shouldBe(3000);
    }

    public function it_should_get_token_rate()
    {
        $this->getTokenRate()->shouldBe(2000);
    }
}
