<?php

declare(strict_types=1);

namespace Minds\Core\ActivityPub;

use Minds\Core\ActivityPub\Factories\ActivityFactory;
use Minds\Core\ActivityPub\Factories\ActorFactory;
use Minds\Core\ActivityPub\Factories\LikeFactory;
use Minds\Core\ActivityPub\Factories\ObjectFactory;
use Minds\Core\ActivityPub\Factories\OutboxFactory;
use Minds\Core\ActivityPub\Services\EmitActivityService;
use Minds\Core\ActivityPub\Services\ProcessActivityService;
use Minds\Core\ActivityPub\Services\ProcessActorService;
use Minds\Core\ActivityPub\Services\ProcessCollectionService;
use Minds\Core\ActivityPub\Services\ProcessObjectService;
use Minds\Core\Subscriptions;
use Minds\Core\Data\cache\InMemoryCache;
use Minds\Core\Di\Di;
use Minds\Core\Di\Provider as DiProvider;
use Minds\Core\Entities\Actions\Save;
use Minds\Core\Feeds\Elastic\V2\Manager as FeedsManager;
use Minds\Core\Media\Image\ProcessExternalImageService;
use Minds\Core\Webfinger;

class Provider extends DiProvider
{
    /**
     * @return void
     * @throws \Minds\Core\Di\ImmutableException
     */
    public function register(): void
    {
        $this->di->bind(Client::class, function (Di $di): Client {
            return new Client(
                httpClient: $di->get(\GuzzleHttp\Client::class),
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
                actorFactory: $di->get(ActorFactory::class),
                outboxFactory: $di->get(OutboxFactory::class),
                objectFactory: $di->get(ObjectFactory::class),
                activityFactory: $di->get(ActivityFactory::class),
                likeFactory: $di->get(LikeFactory::class),
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
                avatarService: $di->get('Channels\AvatarService'),
            );
        });
        $this->di->bind(ProcessActivityService::class, function ($di) {
            return new ProcessActivityService(
                manager: $di->get(Manager::class),
                processActorService: $di->get(ProcessActorService::class),
                processObjectService: $di->get(ProcessObjectService::class),
                emitActivityService: $di->get(EmitActivityService::class),
                acl: $di->get('Security\ACL'),
                activityManager: $di->get('Feeds\Activity\Manager'),
                subscriptionsManager: $di->get(Subscriptions\Manager::class),
                votesManager: $di->get('Votes\Manager'),
                userReportsManager: $di->get('Moderation\UserReports\Manager'),
                processExternalImageService: $di->get(ProcessExternalImageService::class),
                config: $di->get('Config'),
                logger: $di->get('Logger'),
            );
        });
        $this->di->bind(ProcessObjectService::class, function (Di $di): ProcessObjectService {
            return new ProcessObjectService(
                manager: $di->get(Manager::class),
                processActorService: $di->get(ProcessActorService::class),
                metascraperService: $di->get('Metascraper\Service'),
                emitActivityService: $di->get(EmitActivityService::class),
                objectFactory: $di->get(ObjectFactory::class),
                acl: $di->get('Security\ACL'),
                activityManager: $di->get('Feeds\Activity\Manager'),
                subscriptionsManager: $di->get(Subscriptions\Manager::class),
                votesManager: $di->get('Votes\Manager'),
                processExternalImageService: $di->get(ProcessExternalImageService::class),
                config: $di->get('Config'),
                logger: $di->get('Logger'),
                save: new Save(),
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
                cache: $di->get(InMemoryCache::class),
            );
        });
        $this->di->bind(ActivityFactory::class, function ($di) {
            return new ActivityFactory(
                manager: $di->get(Manager::class),
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
                manager: $di->get(Manager::class),
                feedsManager: $di->get(FeedsManager::class),
                objectFactory: $di->get(ObjectFactory::class),
                actorFactory: $di->get(ActorFactory::class),
                activityFactory: $di->get(ActivityFactory::class),
            );
        });
        $this->di->bind(LikeFactory::class, function (Di $di): LikeFactory {
            return new LikeFactory(
                votesManager: $di->get('Votes\Manager'),
                objectFactory: $di->get(ObjectFactory::class),
            );
        });
    }
}
