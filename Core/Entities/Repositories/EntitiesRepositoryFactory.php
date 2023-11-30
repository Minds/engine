<?php
declare(strict_types=1);

namespace Minds\Core\Entities\Repositories;

use Minds\Core\Di\Di;

/**
 * EntitiesRepository Factory to be used when we need to get an instance of EntitiesRepositoryInterface and
 * the tenant config is changed after the DI of a class has been resolved.
 */
class EntitiesRepositoryFactory
{
    public function getInstance(): EntitiesRepositoryInterface
    {
        return Di::_()->get(EntitiesRepositoryInterface::class);
    }
}
