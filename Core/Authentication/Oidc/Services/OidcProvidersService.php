<?php
namespace Minds\Core\Authentication\Oidc\Services;

use Minds\Core\Authentication\Oidc\Models\OidcProvider;
use Minds\Core\Authentication\Oidc\Repositories\OidcProvidersRepository;
use Minds\Core\Security\Vault\VaultTransitService;

class OidcProvidersService
{
    public function __construct(
        private OidcProvidersRepository $oidcProvidersRepository,
        private VaultTransitService $vaultTransitService,
    ) {
        
    }

    /**
     * Return a list of all providers that have been configured
     * @return OidcProvider[]
     */
    public function getProviders(): array
    {
        return $this->oidcProvidersRepository->getProviders();
    }

    /**
     * Return a single provider from an id
     */
    public function getProviderById(int $id): ?OidcProvider
    {
        return $this->oidcProvidersRepository->getProviders($id)[0] ?? null;
    }

    /**
     * Adds a new provider and returns the provider model with its id
     * Also encrypts the client secret
     */
    public function addProvider(
        string $name,
        string $issuer,
        string $clientId,
        string $clientSecret,
    ): OidcProvider {
        $provider =  new OidcProvider(
            id: null,
            name: $name,
            issuer: $issuer,
            clientId: $clientId,
            clientSecretCipherText: $this->vaultTransitService->encrypt($clientSecret)
        );
        return $this->oidcProvidersRepository->addProvider($provider);
    }

    /**
     * Updates a provider
     * Also encrypts the client secret
     */
    public function updateProvider(
        int $id,
        string $name = null,
        string $issuer = null,
        string $clientId = null,
        string $clientSecret = null,
    ): OidcProvider {
        return $this->oidcProvidersRepository->updateProvider(
            providerId: $id,
            name: $name,
            issuer: $issuer,
            clientId: $clientId,
            clientSecret: $clientSecret ? $this->vaultTransitService->encrypt($clientSecret) : null,
        );
    }

    /**
     * Deletes a provider by its id
     */
    public function deleteProvider(int $providerId): bool
    {
        return $this->oidcProvidersRepository->deleteProvider($providerId);
    }
}
