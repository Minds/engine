<?php
/**
 * Minds Subscriptions Provider
 */

namespace Minds\Core\Subscriptions;

use Minds\Core\Di\Di;
use Minds\Core\Di\Provider as DiProvider;

class Provider extends DiProvider
{
    public function register()
    {
        $this->di->bind(Manager::class, function ($di) {
            return new Manager();
        }, [ 'useFactory'=>false ]);

        // Relational

        $this->di->bind('Subscriptions\Relational\Controller', function ($di) {
            return new Relational\Controller();
        }, [ 'useFactory'=>false ]);
        $this->di->bind('Subscriptions\Relational\Repository', function (Di $di): Relational\Repository {
            return $di->get(Relational\Repository::class);
        }, [ 'useFactory'=>false ]);
        $this->di->bind(
            Relational\Repository::class,
            fn (Di $di): Relational\Repository => new Relational\Repository()
        );

        // Graph

        $this->di->bind('Subscriptions\Graph\Controller', function ($di) {
            return new Graph\Controller();
        }, [ 'useFactory' => true ]);
    }
}
