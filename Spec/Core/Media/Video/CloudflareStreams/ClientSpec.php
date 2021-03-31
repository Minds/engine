<?php

namespace Spec\Minds\Core\Media\Video\CloudflareStreams;

use Minds\Core\Media\Video\CloudflareStreams\Client;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use GuzzleHttp;
use GuzzleHttp\Psr7\Response;
use Minds\Core\Config\Config;

class ClientSpec extends ObjectBehavior
{
    protected $httpClient;
    protected $config;

    public function let(GuzzleHttp\Client $httpClient, Config $config)
    {
        $this->beConstructedWith($httpClient, $config);
        $this->httpClient = $httpClient;
        $this->config = $config;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Client::class);
    }

    public function it_should_send_request()
    {
        $this->config->get('cloudflare')
            ->willReturn([
                'api_key'  => 'api-key',
                'email' => 'email@phpspec',
                'account_id' => 'account-id'
            ]);
    
        $this->httpClient->request('GET', "https://api.cloudflare.com/client/v4/accounts/account-id/foobar", Argument::any())
            ->willReturn(new Response(200));

        $this->request('GET', 'foobar');
    }
}
