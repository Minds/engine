<?php

namespace Minds\Core\Boost;

use Minds\Core\Data\Client;
use Minds\Core\Di\Provider;

/**
 * Boost Providers
 */
class BoostProvider extends Provider
{
    /**
     * Registers providers onto DI
     * @return void
     */
    public function register()
    {
        $this->di->bind('Boost\Repository', function ($di) {
            return new Repository();
        }, [ 'useFactory' => true ]);

        $this->di->bind('Boost\Network', function ($di) {
            return new Network([], Client::build('MongoDB'), new Data\Call('entities_by_time'));
        }, ['useFactory' => true]);
        $this->di->bind('Boost\Newsfeed', function ($di) {
            return new Newsfeed([], Client::build('MongoDB'), new Data\Call('entities_by_time'));
        }, ['useFactory' => true]);
        $this->di->bind('Boost\Content', function ($di) {
            return new Content([], Client::build('MongoDB'), new Data\Call('entities_by_time'));
        }, ['useFactory' => true]);

        $this->di->bind('Boost\Peer', function ($di) {
            return new Peer();
        }, ['useFactory' => true]);

        $this->di->bind('Boost\Payment', function ($di) {
            return new Payment();
        }, ['useFactory' => true]);
    }
}
