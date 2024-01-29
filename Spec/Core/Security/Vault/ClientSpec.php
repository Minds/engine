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

    public function it_should_request_an_auth_token_from_kubernetes()
    {
        $this->configMock->get('vault')
            ->willReturn([
                'url' => 'https://vault:8200',
                'auth_method' => 'kubernetes',
                'auth_role' => 'phpspec',
                'auth_jwt_filename' => dirname(__FILE__)  . '/jwt.txt',
                'ca_cert' => '/tmp/ca.crt'
            ]);

        $this->configMock->get('http_proxy')
            ->willReturn(null);

        $this->httpClientMock->request('POST', 'https://vault:8200/v1/auth/kubernetes/login', [
                'verify' => '/tmp/ca.crt',
                'json' => [
                    'jwt' => 'test-jwt-token',
                    'role' => 'phpspec'
                ],
            ])
            ->shouldBeCalled()
            ->willReturn(new JsonResponse([
                'auth' => [
                    'client_token' => 'token-from-k8s'
                ]
            ]));

        $this->httpClientMock->request('POST', 'https://vault:8200/v1/test', [
            'verify' => '/tmp/ca.crt',
            'headers' => [
                'Authorization' => 'Bearer token-from-k8s',
            ],
            'json' => [],
        ])
        ->shouldBeCalled()
        ->willReturn(new JsonResponse([]));

        $response = $this->request('POST', '/test');
        $response->shouldBeAnInstanceOf(ResponseInterface::class);
    }
}
