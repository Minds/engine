<?php

namespace Spec\Minds\Core\Blockchain\UnstoppableDomains;

use Minds\Core\Blockchain\UnstoppableDomains\Client;
use Minds\Core\Config;
use Minds\Core\Http\Curl\Json;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ClientSpec extends ObjectBehavior
{
    private $httpMock;
    private $configMock;

    public function let(Json\Client $http, Config $config)
    {
        $this->beConstructedWith($http, $config);

        $this->httpMock = $http;
        $this->configMock = $config;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Client::class);
    }

    public function it_should_return_domain()
    {
        $this->httpMock->get('https://resolve.unstoppabledomains.com/reverse/0xda730bDa67b84620412b0f26E616803ac213fB3B', Argument::any())
            ->willReturn([
                'meta' => [
                    'domain' => 'minds.eth',
                ]
                ]);

        $this->getDomains('0xda730bDa67b84620412b0f26E616803ac213fB3B')
            ->shouldBe(['minds.eth']);
    }
}
