<?php
declare(strict_types=1);

namespace Minds\Core\Boost\V3;

use Minds\Core\Boost\V3\Repositories\BoostRepository;
use Minds\Core\Boost\V3\Repositories\BoostSummaryRepository;
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
        Di::_()->bind('Boost\V3\Controller', function ($di): Controller {
            return new Controller();
        });
        Di::_()->bind('Boost\V3\Manager', function ($di): Manager {
            return new Manager();
        });
        Di::_()->bind('Boost\V3\Repository', function ($di): Repository {
            return new Repository();
        });
    }
}
