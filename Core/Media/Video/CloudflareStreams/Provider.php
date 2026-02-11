<?php

namespace Minds\Core\Media\Video\CloudflareStreams;

use Minds\Core\Di\Di;
use Minds\Core\Di\Provider as DiProvider;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Media\Video\CloudflareStreams\Services\PruneVideosService;
use Minds\Core\Storage\Quotas\Manager as StorageQuotasManager;

class Provider extends DiProvider
{
    public function register()
    {
        $this->di->bind('Media\Video\CloudflareStreams\Manager', function ($di) {
            return new Manager();
        });
        $this->di->bind('Media\Video\CloudflareStreams\Controllers', function ($di) {
            return new Controllers();
        });
        $this->di->bind('Media\Video\CloudflareStreams\Webhooks', function (Di $di) {
            return new Webhooks(
                storageQuotasManager: $di->get(StorageQuotasManager::class)
            );
        });
        $this->di->bind(PruneVideosService::class, function ($di) {
            return new PruneVideosService(
                cfClient: new Client(),
                logger: $di->get('Logger'),
                entitiesBuilder: $di->get(EntitiesBuilder::class),
            );
        });
    }
}
