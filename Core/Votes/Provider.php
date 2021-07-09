<?php

/**
 * Minds Votes Provider
 *
 * @author emi
 */

namespace Minds\Core\Votes;

use Minds\Core\Di;

class Provider extends Di\Provider
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
    }
}
