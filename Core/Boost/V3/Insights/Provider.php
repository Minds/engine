<?php
declare(strict_types=1);

namespace Minds\Core\Boost\V3\Insights;

use Minds\Core\Di\Di;
use Minds\Core\Di\Provider as DiProvider;

class Provider extends DiProvider
{
    /**
     * @return void
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
        Di::_()->bind(ViewsScroller::class, function ($di): ViewsScroller {
            return new ViewsScroller();
        });
    }
}
