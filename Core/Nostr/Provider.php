<?php

namespace Minds\Core\Nostr;

use Minds\Core\Di;

class Provider extends Di\Provider
{
    public function register()
    {
        $this->di->bind('Nostr\Manager', function ($di) {
            return new Manager();
        });
        $this->di->bind('Nostr\Controller', function ($di) {
            return new Controller();
        });
        $this->di->bind('Nostr\PocSync', function ($di) {
            return new PocSync();
        });
    }
}
