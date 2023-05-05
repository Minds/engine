<?php

namespace Minds\Core\Feeds\Activity\RichEmbed;

use Minds\Core\Di;

class Provider extends Di\Provider
{
    public function register()
    {
        $this->di->bind('Metascraper\Controller', function ($di) {
            return new Metascraper\Controller();
        });

        $this->di->bind('Metascraper\Service', function ($di) {
            return new Metascraper\Service();
        });
    }
}
