<?php

namespace Minds\Core\Security\Vault;

use Minds\Core\Config\Config;
use Minds\Core\Di\Provider as DiProvider;

class Provider extends DiProvider
{
    public function register()
    {
        $this->di->bind(Client::class, function ($di) {
            return new Client(
                httpClient: new \GuzzleHttp\Client(),
                config: $di->get('Config')
            );
        });
        $this->di->bind(VaultTransitService::class, function ($di) {
            return new VaultTransitService(
                client: $di->get(Client::class),
                config: $di->get(Config::class)
            );
        });
    }
}
