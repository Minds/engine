<?php
declare(strict_types=1);

namespace Minds\Core\Monetization\Demonetization;

use Minds\Core\Monetization\Demonetization\Strategies\DemonetizePlusUserStrategy;
use Minds\Core\Monetization\Demonetization\Strategies\DemonetizePostStrategy;
use Minds\Core\Di\Di;
use Minds\Core\Di\ImmutableException;
use Minds\Core\Di\Provider as DiProvider;

class Provider extends DiProvider
{
    /**
     * @return void
     * @throws ImmutableException
     */
    public function register(): void
    {
        Di::_()->bind(DemonetizationContext::class, function ($di): DemonetizationContext {
            return new DemonetizationContext();
        });
        Di::_()->bind(DemonetizePostStrategy::class, function ($di): DemonetizePostStrategy {
            return new DemonetizePostStrategy();
        });
        Di::_()->bind(DemonetizePlusUserStrategy::class, function ($di): DemonetizePlusUserStrategy {
            return new DemonetizePlusUserStrategy();
        });
    }
}
