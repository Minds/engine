<?php
namespace Minds\Core\GraphQL;

use GraphQL\Type\Schema;
use Minds\Core\Data\cache\APCuCache;
use Minds\Core\Di\Container;
use Minds\Core\Di\Di;
use Minds\Core\Di\Provider as DiProvider;
use TheCodingMachine\GraphQLite\SchemaFactory;

class Provider extends DiProvider
{
    public function register(): void
    {
        $this->di->bind(SchemaFactory::class, function (Di $di, array $args = []): SchemaFactory {
            $cache = new APCuCache();

            /**
             * PSR-11 Container Wrapper
             */
            $container = new Container($di);

            return new SchemaFactory($cache, $container);
        }, [ 'useFactory' => true ]);

        $this->di->bind(Schema::class, function (Di $di, array $args = []): Schema {
            /** @var SchemaFactory $factory */
            $factory = $di->get(SchemaFactory::class);

            /**
             * The library requires some default namespaces
             */
            $factory->addControllerNamespace('Minds\\Core\\GraphQL\\Controllers')
                    ->addTypeNamespace('Minds\\Core\\GraphQL\\Types');

            if (isset($args['auth_service'])) {
                $factory->setAuthenticationService($args['auth_service']);
            }

            // Prod mode?
            if (!$di->get('Config')->minds_debug) {
                $factory->prodMode();
            }

            return $factory->createSchema();
        }, ['useFactory' => true ]);

        $this->di->bind(Controller::class, function (Di $di): Controller {
            return new Controller();
        });
    }
}
