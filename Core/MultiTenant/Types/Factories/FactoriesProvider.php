<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Types\Factories;

use Minds\Core\Di\Di;
use Minds\Core\Di\ImmutableException;
use Minds\Core\Di\Provider;

class FactoriesProvider extends Provider
{
    /**
     * @return void
     * @throws ImmutableException
     */
    public function register(): void
    {
        $this->di->bind(
            TenantFactory::class,
            function(Di $di): TenantFactory {
                return new TenantFactory();
            }
        );
        $this->di->bind(
            NetworkUserFactory::class,
            function(Di $di): NetworkUserFactory {
                return new NetworkUserFactory();
            }
        );
        $this->di->bind(
            NetworkUserFactory::class,
            function(Di $di): NetworkUserFactory {
                return new NetworkUserFactory();
            }
        );
    }
}
