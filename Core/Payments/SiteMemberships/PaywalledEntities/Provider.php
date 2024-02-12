<?php
declare(strict_types=1);

namespace Minds\Core\Payments\SiteMemberships\PaywalledEntities;

use Minds\Core\Config\Config;
use Minds\Core\Data\MySQL\Client;
use Minds\Core\Di\Di;
use Minds\Core\Di\ImmutableException;
use Minds\Core\Di\Provider as DiProvider;
use Minds\Core\Payments\SiteMemberships\PaywalledEntities\Services\CreatePaywalledEntityService;
use Minds\Core\Payments\SiteMemberships\Services\SiteMembershipReaderService;

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
            );
        });

        $this->di->bind(PaywalledEntitiesRepository::class, function (Di $di): PaywalledEntitiesRepository {
            return new PaywalledEntitiesRepository(
                mysqlHandler: $di->get(Client::class),
                config: $di->get(Config::class),
                logger: $di->get('Logger')
            );
        });
    }
}
