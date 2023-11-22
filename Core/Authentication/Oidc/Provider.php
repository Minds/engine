<?php

declare(strict_types=1);

namespace Minds\Core\Authentication\Oidc;

use GuzzleHttp\Client;
use Minds\Controllers\api\v1\entities\entity;
use Minds\Controllers\api\v2\oauth\session;
use Minds\Core\Authentication\Oidc\Repositories\OidcProvidersRepository;
use Minds\Core\Authentication\Oidc\Repositories\OidcUserRepository;
use Minds\Core\Authentication\Oidc\Services\OidcAuthService;
use Minds\Core\Authentication\Oidc\Services\OidcProvidersService;
use Minds\Core\Authentication\Oidc\Services\OidcUserService;
use Minds\Core\Config\Config;
use Minds\Core\Di\Container;
use Minds\Core\Di\Di;
use Minds\Core\Di\ImmutableException;
use Minds\Core\Di\Provider as DiProvider;
use Minds\Core\EntitiesBuilder;

class Provider extends DiProvider
{
    public function register(): void
    {
        $this->di->bind(Controller::class, function (Di $di): Controller {
            return new Controller(
                oidcAuthService: $di->get(OidcAuthService::class),
                oidcProvidersService: $di->get(OidcProvidersService::class),
            );
        });

        /**
         * Services
         */

        $this->di->bind(OidcAuthService::class, function (Di $di): OidcAuthService {
            return new OidcAuthService(
                httpClient: $di->get(Client::class),
                oidcUserService: $di->get(OidcUserService::class),
                sessionsManager: $di->get('Sessions\Manager'),
                config: $di->get(Config::class),
            );
        });

        $this->di->bind(OidcUserService::class, function (Di $di): OidcUserService {
            return new OidcUserService(
                oidcUserRepository: $di->get(OidcUserRepository::class),
                entitiesBuilder: $di->get(EntitiesBuilder::class),
            );
        });

        $this->di->bind(OidcProvidersService::class, function (Di $di): OidcProvidersService {
            return new OidcProvidersService(
                oidcProvidersRepository: $di->get(OidcProvidersRepository::class),
            );
        });

        /**
         * Repositories
         */

        $this->di->bind(OidcUserRepository::class, function (Di $di): OidcUserRepository {
            return new OidcUserRepository(
                $di->get(Config::class),
                $di->get('Database\MySQL\Client'),
                $di->get('Logger'),
            );
        });

        $this->di->bind(OidcProvidersRepository::class, function (Di $di): OidcProvidersRepository {
            return new OidcProvidersRepository(
                $di->get(Config::class),
                $di->get('Database\MySQL\Client'),
                $di->get('Logger'),
            );
        });
    }
}
