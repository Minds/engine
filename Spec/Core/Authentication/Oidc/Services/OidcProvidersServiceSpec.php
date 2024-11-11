<?php

namespace Spec\Minds\Core\Authentication\Oidc\Services;

use Minds\Core\Authentication\Oidc\Models\OidcProvider;
use Minds\Core\Authentication\Oidc\Repositories\OidcProvidersRepository;
use Minds\Core\Authentication\Oidc\Services\OidcProvidersService;
use Minds\Core\Security\Vault\VaultTransitService;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;

class OidcProvidersServiceSpec extends ObjectBehavior
{
    private Collaborator $oidcProvidersRepositoryMock;
    private Collaborator $vaultTransitServiceMock;

    public function let(
        OidcProvidersRepository $oidcProvidersRepositoryMock,
        VaultTransitService $vaultTransitServiceMock,
    ) {
        $this->beConstructedWith($oidcProvidersRepositoryMock, $vaultTransitServiceMock);
        $this->oidcProvidersRepositoryMock = $oidcProvidersRepositoryMock;
        $this->vaultTransitServiceMock = $vaultTransitServiceMock;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(OidcProvidersService::class);
    }

    public function it_should_return_array_of_providers()
    {
        $this->oidcProvidersRepositoryMock->getProviders()
            ->shouldBeCalled()
            ->willReturn([
                new OidcProvider(
                    id: 1,
                    name: 'provider 1',
                    issuer: 'https://phpspec.local',
                    clientId: 'phpspec',
                    clientSecretCipherText: '',
                ),
                new OidcProvider(
                    id: 2,
                    name: 'provider 2',
                    issuer: 'https://phpspec.local',
                    clientId: 'phpspec',
                    clientSecretCipherText: '',
                ),
            ]);

        $result = $this->getProviders();
        $result->shouldHaveCount(2);
        $result[0]->shouldBeAnInstanceOf(OidcProvider::class);
        $result[1]->shouldBeAnInstanceOf(OidcProvider::class);
    }

    public function it_should_return_a_provider()
    {
        $this->oidcProvidersRepositoryMock->getProviders(1)
            ->shouldBeCalled()
            ->willReturn([
                new OidcProvider(
                    id: 1,
                    name: 'provider 1',
                    issuer: 'https://phpspec.local',
                    clientId: 'phpspec',
                    clientSecretCipherText: '',
                ),
            ]);

        $result = $this->getProviderById(1);
        $result->shouldBeAnInstanceOf(OidcProvider::class);
    }

    public function it_should_add_a_provider()
    {
        $this->vaultTransitServiceMock->encrypt('client_secret_raw')
            ->shouldBeCalled()
            ->willReturn('cipher_text');

        $this->oidcProvidersRepositoryMock->addProvider(Argument::that(
            fn ($input) =>
            $input->name === 'name' &&
            $input->issuer === 'issuer' &&
            $input->clientId === 'client_id' &&
            $input->clientSecretCipherText === 'cipher_text'
        ))
            ->shouldBeCalled()
            ->willReturn(new OidcProvider(
                id: 1,
                name: 'name',
                issuer: 'issuer',
                clientId: 'client_id',
                clientSecretCipherText: 'cipher_text',
            ));
    
        $result = $this->addProvider('name', 'issuer', 'client_id', 'client_secret_raw');
        $result->shouldBeAnInstanceOf(OidcProvider::class);
    }

    public function it_should_delete_provider()
    {
        $this->oidcProvidersRepositoryMock->deleteProvider(1)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->deleteProvider(1)->shouldBe(true);
    }
}
