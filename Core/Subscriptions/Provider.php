<?php
/**
 * Minds Subscriptions Provider
 */

namespace Minds\Core\Subscriptions;

use Minds\Core\Di\Provider as DiProvider;

class Provider extends DiProvider
{
    public function register()
    {
        $this->di->bind('Subscriptions\Manager', function ($di) {
            return new Manager();
        }, [ 'useFactory'=>false ]);

        // Relational

        $this->di->bind('Subscriptions\Relational\Controller', function ($di) {
            return new Relational\Controller();
        }, [ 'useFactory'=>false ]);
        $this->di->bind('Subscriptions\Relational\Repository', function ($di) {
            return new Relational\Repository();
        }, [ 'useFactory'=>false ]);

        // Graph

        $this->di->bind('Subscriptions\Graph\Controller', function ($di) {
            return new Graph\Controller();
        }, [ 'useFactory' => true ]);
    }
}
