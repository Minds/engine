<?php
namespace Minds\Core\Authentication\Oidc\Controllers;

use Minds\Core\Authentication\Oidc\GqlTypes\OidcProviderPublic;
use Minds\Core\Authentication\Oidc\Models\OidcProvider;
use Minds\Core\Authentication\Oidc\Services\OidcProvidersService;
use Minds\Core\Config\Config;
use Minds\Entities\User;
use TheCodingMachine\GraphQLite\Annotations\InjectUser;
use TheCodingMachine\GraphQLite\Annotations\Logged;
use TheCodingMachine\GraphQLite\Annotations\Mutation;
use TheCodingMachine\GraphQLite\Annotations\Query;
use TheCodingMachine\GraphQLite\Annotations\Security;

class OidcGqlController
{
    public function __construct(
        private readonly OidcProvidersService $oidcProvidersService,
        private readonly Config $config,
    ) {
        
    }

    /**
     * @return OidcProviderPublic[]
     */
    #[Query]
    public function getOidcProviders(): array
    {
        $providers = $this->oidcProvidersService->getProviders();

        return array_map(function (OidcProvider $provider) {
            return new OidcProviderPublic($provider, $this->config);
        }, $providers);
    }

    /**
     * Adds an oidc provider
     * @param string[] $configs
     */
    #[Mutation]
    #[Logged]
    #[Security("is_granted('ROLE_ADMIN', loggedInUser)")]
    public function addOidcProvider(
        string $name,
        string $issuer,
        string $clientId,
        string $clientSecret,
        array $configs = [],
        #[InjectUser] ?User $loggedInUser = null,
    ): OidcProviderPublic {
        $provider = $this->oidcProvidersService->addProvider(
            name: $name,
            issuer: $issuer,
            clientId: $clientId,
            clientSecret: $clientSecret,
            configs: $configs,
        );

        return new OidcProviderPublic($provider, $this->config);
    }

    
    /**
     * Update an oidc provider
     * @param string[] $configs
     */
    #[Mutation]
    #[Logged]
    #[Security("is_granted('ROLE_ADMIN', loggedInUser)")]
    public function updateOidcProvider(
        int $id,
        ?string $name = null,
        ?string $issuer = null,
        ?string $clientId = null,
        ?string $clientSecret = null,
        ?array $configs = null,
        #[InjectUser] ?User $loggedInUser = null,
    ): OidcProviderPublic {
        $provider = $this->oidcProvidersService->updateProvider(
            id: $id,
            name: $name,
            issuer: $issuer,
            clientId: $clientId,
            clientSecret: $clientSecret,
            configs: $configs,
        );

        return new OidcProviderPublic($provider, $this->config);
    }

    /**
     * Delete Oidc Provider
     */
    #[Mutation]
    #[Logged]
    #[Security("is_granted('ROLE_ADMIN', loggedInUser)")]
    public function deleteOidcProvider(
        int $id,
        #[InjectUser] ?User $loggedInUser = null,
    ): bool {
        return $this->oidcProvidersService->deleteProvider($id);
    }
}
