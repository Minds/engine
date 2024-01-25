<?php

namespace Spec\Minds\Core\Security\Vault;

use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Request;
use Http\Factory\Guzzle\ServerRequestFactory;
use Minds\Core\Config\Config;
use Minds\Core\Security\Vault\VaultTransitService;
use Minds\Core\Security\Vault\Client;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;
use Zend\Diactoros\Response\JsonResponse;

class VaultTransitServiceSpec extends ObjectBehavior
{
    protected Collaborator $clientMock;
    protected Collaborator $configMock;

    public function let(Client $clientMock, Config $configMock)
    {
        $this->beConstructedWith($clientMock, $configMock);

        $this->clientMock = $clientMock;
        $this->configMock = $configMock;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(VaultTransitService::class);
    }

    public function it_should_return_cipher_text_when_encrypting()
    {
        $this->clientMock->request('POST', '/transit/encrypt/tenant--1', [
            'plaintext' => base64_encode('hello-world'),
        ])->willReturn(new JsonResponse([
            'data' => [
                'ciphertext' => 'vault:v1:whjqt6O4Trkrkww7/r+Y1QAT6yoZKfEGK9gk01kSqnKJMC6+5DZD'
            ]
        ]));

        $this->encrypt('hello-world')->shouldBe('vault:v1:whjqt6O4Trkrkww7/r+Y1QAT6yoZKfEGK9gk01kSqnKJMC6+5DZD');
    }

    public function it_should_create_key_if_not_found_whilst_encrypting(Request $requestMock)
    {
        $callCount = 0;

        $this->clientMock->request('POST', '/transit/encrypt/tenant--1', [
            'plaintext' => base64_encode('hello-world'),
        ])
        ->shouldBeCalled()
        ->will(function () use (&$callCount, $requestMock) {
            if (++$callCount === 1) {
                throw new ClientException("Key not found", $requestMock->getWrappedObject(), new JsonResponse([], 404));
            }
            //2nd call
            return new JsonResponse([
                'data' => [
                    'ciphertext' => 'vault:v1:whjqt6O4Trkrkww7/r+Y1QAT6yoZKfEGK9gk01kSqnKJMC6+5DZD'
                ]
            ]);
        });

        $this->clientMock->request('POST', '/transit/keys/tenant--1', Argument::type('array'))
            ->shouldBeCalled()
            ->willReturn(new JsonResponse([]));


        $this->encrypt('hello-world')->shouldBe('vault:v1:whjqt6O4Trkrkww7/r+Y1QAT6yoZKfEGK9gk01kSqnKJMC6+5DZD');
    }

    public function it_should_decrypt_key()
    {
        $this->clientMock->request('POST', '/transit/decrypt/tenant--1', [
            'ciphertext' => 'vault:v1:whjqt6O4Trkrkww7/r+Y1QAT6yoZKfEGK9gk01kSqnKJMC6+5DZD',
        ])->willReturn(new JsonResponse([
            'data' => [
                'plaintext' => base64_encode('hello-world')
            ]
        ]));

        $this->decrypt('vault:v1:whjqt6O4Trkrkww7/r+Y1QAT6yoZKfEGK9gk01kSqnKJMC6+5DZD')->shouldBe('hello-world');
    }
}
