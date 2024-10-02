<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Bootstrap\Controllers;

use Minds\Core\Di\Di;
use Minds\Core\Di\ImmutableException;
use Minds\Core\Di\Provider as DiProvider;
use Minds\Core\MultiTenant\Bootstrap\Controllers\BootstrapProgressPsrController;
use Minds\Core\MultiTenant\Bootstrap\Services\BootstrapProgressService;

class ControllersProvider extends DiProvider
{
    /**
     * @return void
     * @throws ImmutableException
     */
    public function register(): void
    {
        $this->di->bind(
            BootstrapProgressPsrController::class,
            function (Di $di): BootstrapProgressPsrController {
                return new BootstrapProgressPsrController(
                    bootstrapProgressService: $di->get(BootstrapProgressService::class)
                );
            }
        );
    }
}
