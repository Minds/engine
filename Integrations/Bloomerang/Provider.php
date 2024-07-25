<?php
declare(strict_types=1);

namespace Minds\Integrations\Bloomerang;

use GuzzleHttp\Client as HttpClient;
use Minds\Core\Config\Config;
use Minds\Core\Data\MySQL\Client;
use Minds\Core\Di\Di;
use Minds\Core\Di\ImmutableException;
use Minds\Core\Di\Provider as DiProvider;
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
        $this->di->bind(
            Events::class,
            fn (Di $di): Events => new Events(
                eventsDispatcher: $di->get('EventsDispatcher'),
                config: $di->get(Config::class),
            )
        );

        $this->di->bind(Repository::class, fn (Di $di) =>
            new Repository(
                mysqlHandler: $di->get(Client::class),
                config: $di->get(Config::class),
                logger: $di->get('Logger'),
            ));

        $this->di->bind(BloomerangConstituentService::class, fn (Di $di) =>
            new BloomerangConstituentService(
                config: $di->get(Config::class),
                httpClient: $di->get(HttpClient::class),
                siteMembershipReaderService: $di->get(SiteMembershipReaderService::class),
                siteMembershipSubscriptionsService: $di->get(SiteMembershipSubscriptionsService::class),
                repository: $di->get(Repository::class),
            ));
    }
}
