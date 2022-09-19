<?php

namespace Spec\Minds\Core\Supermind\Settings\Models;

use Minds\Core\Config\Config;
use Minds\Core\Supermind\Settings\Models\Settings;
use PhpSpec\ObjectBehavior;

class SettingsSpec extends ObjectBehavior
{
    /** @var Config */
    private $config;

    /** @var float */
    private $minOffchainTokens;

    /** @var float */
    private $minCash;
    
    public function let(Config $config)
    {
        $this->beConstructedWith(1, 10, $config);
        $this->config = $config;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Settings::class);
    }

    public function it_should_serialize_class()
    {
        $this->beConstructedWith(1, 10, $this->config);

        $data = [
            'min_offchain_tokens' => 1,
            'min_cash' => 10
        ];

        $this->jsonSerialize()->shouldBeLike($data);
    }
}
