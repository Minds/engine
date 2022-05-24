<?php

namespace Minds\Core\DID;

use Minds\Core\Di;

class Provider extends Di\Provider
{
    public function register()
    {
        $this->di->bind('DID\Manager', function ($di) {
            return new Manager();
        });
        $this->di->bind('DID\Controller', function ($di) {
            return new Controller();
        });
        $this->di->bind('DID\Keypairs\Manager', function ($di) {
            return new Keypairs\Manager();
        });
        //
        $this->di->bind('DID\UniResolver\Manager', function ($di) {
            return new UniResolver\Manager();
        });
        $this->di->bind('DID\UniResolver\Controller', function ($di) {
            return new UniResolver\Controller();
        });
    }
}
