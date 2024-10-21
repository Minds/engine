<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Delegates;

use Minds\Core\Di\Di;
use Minds\Core\Di\ImmutableException;
use Minds\Core\Di\Provider;
use Minds\Core\EntitiesBuilder;

class DelegatesProvider extends Provider
{
    /**
     * @return void
     * @throws ImmutableException
     */
    public function register(): void
    {
        $this->di->bind(FeaturedEntityAddedDelegate::class, function (Di $di): FeaturedEntityAddedDelegate {
            return new FeaturedEntityAddedDelegate(
                $di->get('EventStreams\Topics\ActionEventsTopic'),
                $di->get(EntitiesBuilder::class),
                $di->get('Logger')
            );
        });
    }
}
