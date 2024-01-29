<?php

namespace Spec\Minds\Core\Security\Vault;

use Minds\Core\Config\Config;
use Minds\Core\Security\Vault\Client;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Psr\Http\Message\ResponseInterface;
use Zend\Diactoros\Response\JsonResponse;

class ClientSpec extends ObjectBehavior
{
    protected Collaborator $httpClientMock;
    protected Collaborator $configMock;

    public function let(\GuzzleHttp\Client $httpClientMock, Config $configMock)
    {
        $this->beConstructedWith($httpClientMock, $configMock);
        $this->httpClientMock = $httpClientMock;
        $this->configMock = $configMock;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Client::class);
    }

    public function it_should_send_request()
    {
        $this->configMock->get('vault')
            ->willReturn([
                'url' => 'https://vault:8200',
                'token' => 'super-secret-token',
                'auth_method' => 'token',
            ]);

        $this->configMock->get('http_proxy')
            ->willReturn(null);

        $this->httpClientMock->request('POST', 'https://vault:8200/v1/test', [
            'headers' => [
                'Authorization' => 'Bearer super-secret-token',
            ],
            'json' => [],
        ])
            ->shouldBeCalled()
            ->willReturn(new JsonResponse([]));

        $response = $this->request('POST', '/test');
        $response->shouldBeAnInstanceOf(ResponseInterface::class);
    }
}
