<?php
declare(strict_types=1);

namespace Minds\Core\Boost\V3\Summaries;

use Minds\Core\Config\Config;
use Minds\Core\Data\MySQL\Client;
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
            return new Repository(
                mysqlHandler: $di->get(Client::class),
                config: $di->get(Config::class),
                logger: $di->get('Logger'),
            );
        });
    }
}
