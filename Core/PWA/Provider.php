<?php
declare(strict_types=1);

namespace Minds\Core\PWA;

use Minds\Core\Config\Config;
use Minds\Core\Di\Provider as DiProvider;
use Minds\Core\PWA\Controllers\ManifestController;
use Minds\Core\PWA\Services\ManifestService;

/**
 * PWA Provider
 */
class Provider extends DiProvider
{
    /**
     * Register providers.
     */
    public function register(): void
    {
        $this->di->bind(ManifestController::class, function ($di): ManifestController {
            return new ManifestController(
                service: $di->get(ManifestService::class)
            );
        });

        $this->di->bind(ManifestService::class, function ($di): ManifestService {
            return new ManifestService(
                config: $di->get(Config::class)
            );
        });
    }
}
