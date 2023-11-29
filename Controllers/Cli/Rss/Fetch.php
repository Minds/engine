<?php
declare(strict_types=1);

namespace Minds\Controllers\Cli\Rss;

use Minds\Cli\Controller;
use Minds\Core\Di\Di;
use Minds\Core\Feeds\RSS\Services\Service as RssFeedService;
use Minds\Core\Log\Logger;
use Minds\Core\MultiTenant\Exceptions\NoTenantFoundException;
use Minds\Exceptions\ServerErrorException;
use Minds\Interfaces\CliControllerInterface;

class Fetch extends Controller implements CliControllerInterface
{
    private readonly Logger $logger;
    public function __construct()
    {
        Di::_()->get('Config')
            ->set('min_log_level', 'INFO');

        $this->logger = Di::_()->get('Logger');
    }

    public function help($command = null): void
    {
        // TODO: Implement help() method.
    }

    /**
     * @return void
     * @throws NoTenantFoundException
     * @throws ServerErrorException
     */
    public function exec(): void
    {
        (function () {
            /**
             * @var RssFeedService $rssFeedService
             */
            $rssFeedService = Di::_()->get(RssFeedService::class);

            $dryRun = $this->getOpt('dry-run') ?? false;

            $this->logger->info('Processing RSS feeds');
            $rssFeedService->processFeeds((bool)$dryRun);
        })();
    }
}
