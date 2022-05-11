<?php

namespace Spec\Minds\Core\DID\UniResolver;

use Minds\Core\Http\Curl\Json;
use Minds\Core\Config\Config;
use Minds\Core\DID\UniResolver\Client;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ClientSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(Client::class);
    }

    public function it_should_make_a_request(Json\Client $http, Config $config)
    {
        $this->beConstructedWith($http, $config);

        $config->get('did')
            ->willReturn([
                'uniresolver' => [
                    'base_url' => 'https://uniresolver.minds.io/',
                ]
            ]);

        $http->get('https://uniresolver.minds.io/1.0/identifiers/did:web:minds.com:mark', Argument::any())
            ->willReturn([
                'foo' => 'bar'
            ]);

        $this->request('/1.0/identifiers/did:web:minds.com:mark')
            ->shouldBe([
                'foo' => 'bar'
            ]);
    }
}
