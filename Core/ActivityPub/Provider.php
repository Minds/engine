<?php

declare(strict_types=1);

namespace Minds\Core\ActivityPub;

use Minds\Core\Di\Provider as DiProvider;
use Minds\Core\Webfinger;
use Minds\Core\ActivityPub\Services\ProcessActivityService;
use Minds\Core\ActivityPub\Services\ProcessActorService;
use Minds\Core\ActivityPub\Services\ProcessCollectionService;
use Minds\Core\Entities\Actions\Save;

class Provider extends DiProvider
{
    /**
     * @return void
     * @throws \Minds\Core\Di\ImmutableException
     */
    public function register(): void
    {
        $this->di->bind(Client::class, function ($di) {
            return new Client(
                httpClient: new \GuzzleHttp\Client(),
                config: $di->get('Config'),
            );
        });
        $this->di->bind(Repository::class, function ($di) {
            return new Repository($di->get('Database\MySQL\Client'), $di->get('Logger'));
        });
        $this->di->bind(Manager::class, function ($di) {
            return new Manager(
                repository: $di->get(Repository::class),
                entitiesBuilder: $di->get('EntitiesBuilder'),
                config: $di->get('Config'),
                client: $di->get(Client::class),
                webfingerManager: $di->get(Webfinger\Manager::class),
            );
        });
        $this->di->bind(Controller::class, function ($di) {
            return new Controller(
                manager: $di->get(Manager::class),
                entitiesBuilder: $di->get('EntitiesBuilder'),
                config: $di->get('Config'),
            );
        });

        /**
         * Services
         */
        $this->di->bind(ProcessActorService::class, function ($di) {
            return new ProcessActorService(
                manager: $di->get(Manager::class),
                acl: $di->get('Security\ACL'),
                saveAction: new Save(),
            );
        });
        $this->di->bind(ProcessActivityService::class, function ($di) {
            return new ProcessActivityService(
                manager: $di->get(Manager::class),
                processActorService: $di->get(ProcessActorService::class),
                acl: $di->get('Security\ACL'),
                activityManager: $di->get('Feeds\Activity\Manager'),
            );
        });
        $this->di->bind(ProcessCollectionService::class, function ($di) {
            return new ProcessCollectionService($di->get(ProcessActivityService::class));
        });
    }
}
