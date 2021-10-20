<?php

namespace Minds\Core\SocialCompass;

use Minds\Core\Di\Provider as DiProvider;

class Provider extends DiProvider
{
    public function register() : void
    {
        $this->di->bind('SocialCompass\Manager', function($di) {
            return new Manager();
        });
        $this->di->bind('SocialCompass\Controller', function($di) {
            return new Controller();
        });
    }
}
