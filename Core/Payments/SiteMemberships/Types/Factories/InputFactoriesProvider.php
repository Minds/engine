<?php
declare(strict_types=1);

namespace Minds\Core\Payments\SiteMemberships\Types\Factories;

use Minds\Core\Di\Di;
use Minds\Core\Di\Provider;

class InputFactoriesProvider extends Provider
{
    public function register(): void
    {
        $this->di->bind(
            SiteMembershipInputFactory::class,
            fn (Di $di): SiteMembershipInputFactory => new SiteMembershipInputFactory(
                entitiesBuilder: $di->get('EntitiesBuilder')
            )
        );
    }
}
