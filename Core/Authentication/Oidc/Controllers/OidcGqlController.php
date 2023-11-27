<?php
namespace Minds\Core\Authentication\Oidc\Controllers;

use Minds\Core\Authentication\Oidc\GqlTypes\OidcProviderPublic;
use Minds\Core\Authentication\Oidc\Models\OidcProvider;
use Minds\Core\Authentication\Oidc\Services\OidcProvidersService;
use Minds\Core\Config\Config;
use TheCodingMachine\GraphQLite\Annotations\Query;

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

}
