<?php
declare(strict_types=1);

namespace Minds\Core\SEO\Robots;

use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Core\Di\Provider as DiProvider;
use Minds\Core\SEO\Robots\Controllers\RobotsFileController;
use Minds\Core\SEO\Robots\Services\RobotsFileService;

/**
 * SEO Robots Provider
 */
class Provider extends DiProvider
{
    /**
     * Register providers.
     */
    public function register(): void
    {
        $this->di->bind(RobotsFileController::class, function ($di): RobotsFileController {
            return new RobotsFileController(
                robotsFileService: Di::_()->get(RobotsFileService::class)
            );
        });

        $this->di->bind(RobotsFileService::class, function ($di): RobotsFileService {
            return new RobotsFileService(
                config: Di::_()->get(Config::class)
            );
        });
    }
}
