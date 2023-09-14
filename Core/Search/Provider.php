<?php

/**
 * Search Provider
 *
 * @author emi
 */

namespace Minds\Core\Search;

use Composer\XdebugHandler\Process;
use Minds\Core\ActivityPub\Services\ProcessActorService;
use Minds\Core\Di;
use Minds\Core\Feeds\Elastic;
use Minds\Core\Boost\V3\Manager as BoostManager;
use Minds\Core\Search\Controllers\SearchController;

class Provider extends Di\Provider
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

        $this->di->bind(Controllers\SearchController::class, function (Di\Di $di): SearchController {
            return new Controllers\SearchController(
                elasticFeedsManager: $di->get(Elastic\V2\Manager::class),
                search: $di->get('Search\Search'),
                entitiesBuilder: $di->get('EntitiesBuilder'),
                boostManager: $di->get(BoostManager::class),
                processActorService: $di->get(ProcessActorService::class),
            );
        });
    }
}
