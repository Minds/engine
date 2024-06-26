<?php
declare(strict_types=1);

namespace Minds\Core\Boost\V3;

use Minds\Core\Boost\V3\Delegates\ActionEventDelegate;
use Minds\Core\Boost\V3\GraphQL\Controllers\AdminController;
use Minds\Core\Boost\V3\Utils\BoostConsoleUrlBuilder;
use Minds\Core\Boost\V3\Utils\BoostReceiptUrlBuilder;
use Minds\Core\Boost\V3\PreApproval\Manager as PreApprovalManager;
use Minds\Core\Boost\V3\GraphQL\Controllers\Controller as GraphQLController;
use Minds\Core\Config\Config;
use Minds\Core\Data\MySQL\Client;
use Minds\Core\Di\Di;
use Minds\Core\Di\ImmutableException;
use Minds\Core\Di\Provider as DiProvider;
use Minds\Core\EntitiesBuilder;

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
                entitiesBuilder: $di->get(EntitiesBuilder::class),
                mysqlHandler: $di->get(Client::class),
                config: $di->get(Config::class),
                logger: $di->get('Logger'),
            );
        });
        Di::_()->bind(PreApprovalManager::class, function ($di): PreApprovalManager {
            return new PreApprovalManager();
        });
        Di::_()->bind(ActionEventDelegate::class, function ($di): ActionEventDelegate {
            return new ActionEventDelegate();
        });
        Di::_()->bind(BoostConsoleUrlBuilder::class, function ($di): BoostConsoleUrlBuilder {
            return new BoostConsoleUrlBuilder();
        });
        Di::_()->bind(BoostReceiptUrlBuilder::class, function ($di): BoostReceiptUrlBuilder {
            return new BoostReceiptUrlBuilder();
        });
        Di::_()->bind(Onchain\OnchainBoostBackgroundJob::class, function ($di): Onchain\OnchainBoostBackgroundJob {
            return new Onchain\OnchainBoostBackgroundJob();
        });
        Di::_()->bind(GraphQLController::class, function (Di $di): GraphQLController {
            return new GraphQLController(
                $di->get(Manager::class),
                $di->get('Logger')
            );
        }, ['factory' => true]);
        Di::_()->bind(AdminController::class, function (Di $di): AdminController {
            return new AdminController($di->get(Manager::class));
        }, ['factory' => true]);
    }
}
