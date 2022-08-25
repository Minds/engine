<?php

namespace Minds\Core\Rewards\Restrictions\Blockchain;

use Minds\Core\Di;

class Provider extends Di\Provider
{
    public function register()
    {
        $this->di->bind('Rewards\Restrictions\Blockchain\Controller', function ($di) {
            return new Controller();
        }, [ 'useFactory'=> true ]);

        $this->di->bind('Rewards\Restrictions\Blockchain\Manager', function ($di) {
            return new Manager();
        }, [ 'useFactory'=> true ]);

        $this->di->bind('Rewards\Restrictions\Blockchain\Repository', function ($di) {
            return new Repository();
        }, [ 'useFactory'=> true ]);
    }
}
