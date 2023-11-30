<?php
declare(strict_types=1);

namespace Minds\Core\Feeds\RSS;

use GuzzleHttp\Client;
use Laminas\Feed\Reader\Reader;
use Minds\Core\Di\Di;
use Minds\Core\Di\ImmutableException;
use Minds\Core\Di\Provider as DiProvider;
use Minds\Core\Feeds\RSS\Controllers\Controller;
use Minds\Core\Feeds\RSS\Repositories\MySQLRepository;
use Minds\Core\Feeds\RSS\Services\ProcessRssFeedService;
use Minds\Core\Feeds\RSS\Services\Service;
use Minds\Core\Feeds\RSS\Types\Factories\RssFeedInputFactory;
use Minds\Core\MultiTenant\Services\MultiTenantBootService;

class Provider extends DiProvider
{
    /**
     * @return void
     * @throws ImmutableException
     */
    public function register(): void
    {
        $this->di->bind(
            MySQLRepository::class,
            fn (Di $di): MySQLRepository => new MySQLRepository(
                mysqlHandler: $di->get('Database\MySQL\Client'),
                logger: $di->get('Logger'),
                config: $di->get('Config')
            )
        );
        $this->di->bind(
            Service::class,
            fn (Di $di): Service => new Service(
                processRssFeedService: $di->get(ProcessRssFeedService::class),
                repository: $di->get(MySQLRepository::class),
                multiTenantBootService: $di->get(MultiTenantBootService::class),
                entitiesBuilder: $di->get('EntitiesBuilder'),
                logger: $di->get('Logger')
            )
        );
        $this->di->bind(
            ProcessRssFeedService::class,
            function (Di $di): ProcessRssFeedService {
                $reader = new Reader();
                $reader->setHttpClient($di->get(Psr7RssFeedReaderHttpClient::class));

                return new ProcessRssFeedService(
                    reader: $reader,
                    metaScraperService: $di->get('Metascraper\Service'),
                    activityManager: $di->get('Feeds\Activity\Manager'),
                    acl: $di->get('Security\ACL'),
                    logger: $di->get('Logger')
                );
            }
        );
        $this->di->bind(
            Psr7RssFeedReaderHttpClient::class,
            fn (Di $di): Psr7RssFeedReaderHttpClient => new Psr7RssFeedReaderHttpClient(
                client: new Client()
            )
        );
        $this->di->bind(
            Controller::class,
            fn (Di $di): Controller => new Controller(
                service: $di->get(Service::class)
            )
        );


        $this->di->bind(
            RssFeedInputFactory::class,
            fn (Di $di): RssFeedInputFactory => new RssFeedInputFactory()
        );
    }
}
