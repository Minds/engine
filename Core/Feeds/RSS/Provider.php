<?php
declare(strict_types=1);

namespace Minds\Core\Feeds\RSS;

use GuzzleHttp\Client;
use Laminas\Feed\Reader\Reader;
use Minds\Core\Di\Di;
use Minds\Core\Di\ImmutableException;
use Minds\Core\Di\Provider as DiProvider;
use Minds\Core\Feeds\RSS\ActivityBuilders\AudioActivityBuilder;
use Minds\Core\Feeds\RSS\Controllers\Controller;
use Minds\Core\Feeds\RSS\Repositories\RssFeedsRepository;
use Minds\Core\Feeds\RSS\Repositories\RssImportsRepository;
use Minds\Core\Feeds\RSS\Services\ProcessRssFeedService;
use Minds\Core\Feeds\RSS\Services\ReaderLibraryWrapper;
use Minds\Core\Feeds\RSS\Services\Service;
use Minds\Core\Feeds\RSS\Types\Factories\RssFeedInputFactory;
use Minds\Core\Media\Audio\AudioService;
use Minds\Core\Media\MediaDownloader\ImageDownloader;
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
            RssFeedsRepository::class,
            fn (Di $di): RssFeedsRepository => new RssFeedsRepository(
                mysqlHandler: $di->get('Database\MySQL\Client'),
                config: $di->get('Config'),
                logger: $di->get('Logger'),
            )
        );
        $this->di->bind(
            RssImportsRepository::class,
            fn (Di $di): RssImportsRepository => new RssImportsRepository(
                mysqlHandler: $di->get('Database\MySQL\Client'),
                config: $di->get('Config'),
                logger: $di->get('Logger'),
            )
        );
        $this->di->bind(
            Service::class,
            fn (Di $di): Service => new Service(
                processRssFeedService: $di->get(ProcessRssFeedService::class),
                rssFeedsRepository: $di->get(RssFeedsRepository::class),
                multiTenantBootService: $di->get(MultiTenantBootService::class),
                entitiesBuilder: $di->get('EntitiesBuilder'),
                logger: $di->get('Logger')
            )
        );
        $this->di->bind(
            ReaderLibraryWrapper::class,
            function (Di $di): ReaderLibraryWrapper {
                $reader = new Reader();
                $reader->setHttpClient($di->get(Psr7RssFeedReaderHttpClient::class));
                return new ReaderLibraryWrapper($reader);
            }
        );
        $this->di->bind(
            ProcessRssFeedService::class,
            function (Di $di): ProcessRssFeedService {
                return new ProcessRssFeedService(
                    reader: $di->get(ReaderLibraryWrapper::class),
                    metaScraperService: $di->get('Metascraper\Service'),
                    activityManager: $di->get('Feeds\Activity\Manager'),
                    rssImportsRepository: $di->get(RssImportsRepository::class),
                    audioActivityBuilder: $di->get(AudioActivityBuilder::class),
                    audioService: $di->get(AudioService::class),
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

        $this->di->bind(
            AudioActivityBuilder::class,
            fn (Di $di): AudioActivityBuilder => new AudioActivityBuilder(
                audioService: $di->get(AudioService::class),
                imageDownloader: $di->get(ImageDownloader::class),
                logger: $di->get('Logger'),
            )
        );
    }
}
