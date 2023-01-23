<?php
declare(strict_types=1);

namespace Minds\Core\Boost\V3;

use Minds\Core\Boost\V3\Delegates\ActionEventDelegate;
use Minds\Core\Boost\V3\Utils\BoostConsoleUrlBuilder;
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
        Di::_()->bind(Controller::class, function ($di): Controller {
            return new Controller();
        });
        Di::_()->bind(Manager::class, function ($di): Manager {
            return new Manager();
        });
        Di::_()->bind(Repository::class, function ($di): Repository {
            return new Repository();
        });
        Di::_()->bind(ActionEventDelegate::class, function ($di): ActionEventDelegate {
            return new ActionEventDelegate();
        });
        Di::_()->bind(BoostConsoleUrlBuilder::class, function ($di): BoostConsoleUrlBuilder {
            return new BoostConsoleUrlBuilder();
        });
    }
}
