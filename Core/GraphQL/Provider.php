<?php

namespace Minds\Core\GraphQL;

use GraphQL\Type\Schema;
use Minds\Core\Data\cache\APCuCache;
use Minds\Core\Di\Container;
use Minds\Core\Di\Di;
use Minds\Core\Di\Provider as DiProvider;
use Minds\Core\GraphQL\Client\Client;
use Minds\Core\GraphQL\Services\AuthorizationService;
use Minds\Core\GraphQL\Services\AuthService;
use Minds\Core\Security\Rbac\Services\RolesService;
use Minds\Core\Sessions\ActiveSession;
use TheCodingMachine\GraphQLite\SchemaFactory;

class Provider extends DiProvider
{
    public function register(): void
    {
        $this->di->bind(SchemaFactory::class, function (Di $di, array $args = []): SchemaFactory {
            $cache = new APCuCache();
            // $cache->clear();
            /**
             * PSR-11 Container Wrapper
             */
            $container = new Container($di);

            return new SchemaFactory($cache, $container);
        }, ['useFactory' => true]);

        $this->di->bind(Schema::class, function (Di $di, array $args = []): Schema {
            /** @var SchemaFactory $factory */
            $factory = $di->get(SchemaFactory::class);

            if ($di->get('Config')->minds_debug) {
                $factory->devMode();
            } else {
                $factory->prodMode();
            }

            /**
             * The library requires some default namespaces
             */
            $factory->addControllerNamespace('Minds\\Core\\GraphQL\\Controllers')
                ->addTypeNamespace('Minds\\Core\\GraphQL\\Types');

            $factory->setAuthenticationService($di->get(AuthService::class));

            $factory->setAuthorizationService($di->get(AuthorizationService::class));

            return $factory->createSchema();
        }, ['useFactory' => true]);

        $this->di->bind(Controller::class, function (Di $di): Controller {
            return new Controller();
        });

        $this->di->bind(
            Client::class,
            function (Di $di, array $args = []): Client {
                $httpClient = new \GuzzleHttp\Client([
                    'base_uri' => $args['base_uri'] ?? '',
                ]);
                
                return new Client(
                    httpClient: $httpClient
                );
            }
        );

        $this->di->bind(AuthService::class, function (Di $di): AuthService {
            return new AuthService(new ActiveSession());
        });

        $this->di->bind(AuthorizationService::class, function (Di $di): AuthorizationService {
            return new AuthorizationService($di->get(RolesService::class));
        });
    }
}
