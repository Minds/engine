<?php

/**
 * Minds Votes Provider
 *
 * @author emi
 */

namespace Minds\Core\Votes;

use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Core\Di\Provider as DiProvider;

class Provider extends DiProvider
{
    public function register()
    {
        $this->di->bind('Votes\Counters', function () {
            return new Counters();
        }, [ 'useFactory' => true ]);

        $this->di->bind('Votes\Manager', function () {
            return new Manager();
        }, [ 'useFactory' => true ]);

        $this->di->bind('Votes\Indexes', function () {
            return new Indexes();
        }, [ 'useFactory' => true ]);

        $this->di->bind('Votes\Controller', function () {
            return new Controller();
        }, [ 'useFactory' => true ]);

        $this->di->bind(MySqlRepository::class, function (Di $di): MySqlRepository {
            return new MySqlRepository(
                $di->get('EntitiesBuilder'),
                $di->get(Config::class),
            );
        }, [ 'useFactory' => true ]);
    }
}
