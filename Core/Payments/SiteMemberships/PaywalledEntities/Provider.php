<?php
declare(strict_types=1);

namespace Minds\Core\Payments\SiteMemberships\PaywalledEntities;

use Minds\Core\Config\Config;
use Minds\Core\Data\cache\SharedCache;
use Minds\Core\Data\MySQL\Client;
use Minds\Core\Di\Di;
use Minds\Core\Di\ImmutableException;
use Minds\Core\Di\Provider as DiProvider;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Payments\SiteMemberships\PaywalledEntities\Controllers\PaywalledEntitiesPsrController;
use Minds\Core\Payments\SiteMemberships\PaywalledEntities\Services\CreatePaywalledEntityService;
use Minds\Core\Payments\SiteMemberships\PaywalledEntities\Services\PaywalledEntityGatekeeperService;
use Minds\Core\Payments\SiteMemberships\PaywalledEntities\Services\PaywalledEntityService;
use Minds\Core\Payments\SiteMemberships\Services\SiteMembershipReaderService;
use Minds\Core\Payments\SiteMemberships\Services\SiteMembershipSubscriptionsService;

class Provider extends DiProvider
{
    /**
     * @return void
     * @throws ImmutableException
     */
    public function register(): void
    {
        $this->di->bind(CreatePaywalledEntityService::class, function (Di $di): CreatePaywalledEntityService {
            return new CreatePaywalledEntityService(
                paywalledEntitiesRepository: $di->get(PaywalledEntitiesRepository::class),
                siteMembershipReaderService: $di->get(SiteMembershipReaderService::class),
                imagickManager: $di->get('Media\Imagick\Manager'),
                blurHash: $di->get('Media\BlurHash'),
            );
        });

        $this->di->bind(PaywalledEntityGatekeeperService::class, function (Di $di): PaywalledEntityGatekeeperService {
            return new PaywalledEntityGatekeeperService(
                paywalledEntityService: $di->get(PaywalledEntityService::class),
                siteMembershipSubscriptionsService: $di->get(SiteMembershipSubscriptionsService::class),
                cache: $di->get(SharedCache::class),
            );
        });

        $this->di->bind(PaywalledEntityService::class, function (Di $di): PaywalledEntityService {
            return new PaywalledEntityService(
                paywalledEntitiesRepository: $di->get(PaywalledEntitiesRepository::class),
                siteMembershipReaderService: $di->get(SiteMembershipReaderService::class),
                cache: $di->get(SharedCache::class),
            );
        });

        $this->di->bind(PaywalledEntitiesRepository::class, function (Di $di): PaywalledEntitiesRepository {
            return new PaywalledEntitiesRepository(
                mysqlHandler: $di->get(Client::class),
                config: $di->get(Config::class),
                logger: $di->get('Logger')
            );
        });

        $this->di->bind(PaywalledEntitiesPsrController::class, function (Di $di): PaywalledEntitiesPsrController {
            return new PaywalledEntitiesPsrController(
                entitiesBuilder: $di->get(EntitiesBuilder::class),
                paywalledEntityService: $di->get(PaywalledEntityService::class),
            );
        });
    }
}
