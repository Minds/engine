<?php

namespace Minds\Core\Feeds\Activity\RichEmbed;

use Minds\Core\Di;

class Provider extends Di\Provider
{
    public function register()
    {
        $this->di->bind('Feeds\Activity\RichEmbed\Manager', function ($di) {
            return new Manager($di->get('Feeds\Activity\RichEmbed\Iframely'), $di->get('Config'));
        });

        $this->di->bind('Feeds\Activity\RichEmbed\Iframely', function ($di) {
            return new Iframely(new \GuzzleHttp\Client(), $di->get('Config'));
        });

        $this->di->bind('Metascraper\Controller', function ($di) {
            return new Metascraper\Controller();
        });

        $this->di->bind('Metascraper\Service', function ($di) {
            return new Metascraper\Service();
        });
    }
}
