<?php

/**
 * Search Provider
 *
 * @author emi
 */

namespace Minds\Core\Search;

use Minds\Core\Di\Provider;

class SearchProvider extends Provider
{
    public function register()
    {
        $this->di->bind(Index::class, function ($di) {
            return new Index();
        }, ['useFactory' => true]);

        $this->di->bind('Search\Search', function ($di) {
            return new Search();
        }, ['useFactory' => true]);

        $this->di->bind('Search\Mappings', function ($di) {
            return new Mappings\Factory();
        }, ['useFactory' => true]);

        $this->di->bind('Search\Provisioner', function ($di) {
            return new Provisioner();
        }, ['useFactory' => true]);

        $this->di->bind('Search\Hashtags\Manager', function ($di) {
            return new Hashtags\Manager();
        }, ['useFactory' => true]);

        $this->di->bind('Search\RetryQueue\Manager', function ($di) {
            return new RetryQueue\Manager();
        }, ['useFactory' => true]);
    }
}
