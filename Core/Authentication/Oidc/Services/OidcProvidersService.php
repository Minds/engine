<?php
namespace Minds\Core\Authentication\Oidc\Services;

use Minds\Core\Authentication\Oidc\Models\OidcProvider;
use Minds\Core\Authentication\Oidc\Repositories\OidcProvidersRepository;

class OidcProvidersService
{
    public function __construct(
        private OidcProvidersRepository $oidcProvidersRepository,
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

}
