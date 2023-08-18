<?php

declare(strict_types=1);

namespace Minds\Core\ActivityPub;

use Minds\Core\Di\Provider as DiProvider;
use Minds\Core\Webfinger;
use Minds\Core\ActivityPub\Services\ProcessActivityService;
use Minds\Core\ActivityPub\Services\ProcessActorService;
use Minds\Core\ActivityPub\Services\ProcessCollectionService;
use Minds\Core\ActivityPub\Factories\ActivityFactory;
use Minds\Core\ActivityPub\Factories\ActorFactory;
use Minds\Core\ActivityPub\Factories\ObjectFactory;
use Minds\Core\ActivityPub\Factories\OutboxFactory;
use Minds\Core\ActivityPub\Services\EmitActivityService;
use Minds\Core\Entities\Actions\Save;
use Minds\Core\Feeds\Elastic\V2\Manager as FeedsManager;
use Minds\Core\Media\Image\ProcessExternalImageService;

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
            );
        });
        $this->di->bind(Controller::class, function ($di) {
            return new Controller(
                manager: $di->get(Manager::class),
                actorFactory: $di->get(ActorFactory::class),
                outboxFactory: $di->get(OutboxFactory::class),
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
                actorFactory: $di->get(ActorFactory::class),
                acl: $di->get('Security\ACL'),
                saveAction: new Save(),
            );
        });
        $this->di->bind(ProcessActivityService::class, function ($di) {
            return new ProcessActivityService(
                manager: $di->get(Manager::class),
                processActorService: $di->get(ProcessActorService::class),
                emitActivityService: $di->get(EmitActivityService::class),
                acl: $di->get('Security\ACL'),
                activityManager: $di->get('Feeds\Activity\Manager'),
                subscriptionsManager: $di->get('Subscriptions\Manager'),
                processExternalImageService: $di->get(ProcessExternalImageService::class),
                config: $di->get('Config'),
            );
        });
        $this->di->bind(ProcessCollectionService::class, function ($di) {
            return new ProcessCollectionService(
                processActivityService: $di->get(ProcessActivityService::class),
                activityFactory: $di->get(ActivityFactory::class),
            );
        });
        $this->di->bind(EmitActivityService::class, function ($di) {
            return new EmitActivityService(
                actorFactory: $di->get(ActorFactory::class),
                objectFactory: $di->get(ObjectFactory::class),
                client: $di->get(Client::class),
                manager: $di->get(Manager::class),
                entitiesBuilder: $di->get('EntitiesBuilder'),
                logger: $di->get('Logger'),
            );
        });

        /**
         * Factories
         */
        $this->di->bind(ActorFactory::class, function ($di) {
            return new ActorFactory(
                manager: $di->get(Manager::class),
                client: $di->get(Client::class),
                webfingerManager: $di->get(Webfinger\Manager::class),
                config: $di->get('Config'),
            );
        });
        $this->di->bind(ActivityFactory::class, function ($di) {
            return new ActivityFactory(
                actorFactory: $di->get(ActorFactory::class),
                objectFactory: $di->get(ObjectFactory::class),
            );
        });
        $this->di->bind(ObjectFactory::class, function ($di) {
            return new ObjectFactory(
                manager: $di->get(Manager::class),
                client: $di->get(Client::class),
                actorFactory: $di->get(ActorFactory::class),
            );
        });
        $this->di->bind(OutboxFactory::class, function ($di) {
            return new OutboxFactory(
                feedsManager: $di->get(FeedsManager::class),
                objectFactory: $di->get(ObjectFactory::class),
                actorFactory: $di->get(ActorFactory::class),
            );
        });
    }
}
