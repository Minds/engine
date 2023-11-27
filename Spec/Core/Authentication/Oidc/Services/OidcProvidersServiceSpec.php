<?php

namespace Spec\Minds\Core\Authentication\Oidc\Services;

use Minds\Core\Authentication\Oidc\Models\OidcProvider;
use Minds\Core\Authentication\Oidc\Repositories\OidcProvidersRepository;
use Minds\Core\Authentication\Oidc\Services\OidcProvidersService;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;

class OidcProvidersServiceSpec extends ObjectBehavior
{
    private Collaborator $oidcProvidersRepositoryMock;

    public function let(OidcProvidersRepository $oidcProvidersRepositoryMock)
    {
        $this->beConstructedWith($oidcProvidersRepositoryMock);
        $this->oidcProvidersRepositoryMock = $oidcProvidersRepositoryMock;
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
                    clientSecret: '',
                ),
                new OidcProvider(
                    id: 2,
                    name: 'provider 2',
                    issuer: 'https://phpspec.local',
                    clientId: 'phpspec',
                    clientSecret: '',
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
                    clientSecret: '',
                ),
            ]);

        $result = $this->getProviderById(1);
        $result->shouldBeAnInstanceOf(OidcProvider::class);
    }
}
