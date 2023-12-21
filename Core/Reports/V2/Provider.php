<?php
declare(strict_types=1);

namespace Minds\Core\Reports\V2;

use Minds\Core\Config\Config;
use Minds\Core\Data\MySQL\Client as MySQLClient;
use Minds\Core\Reports\V2\Controllers\ReportController;
use Minds\Core\Di\Di;
use Minds\Core\Di\Provider as DiProvider;
use Minds\Core\Entities\Actions\Delete;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Entities\Resolver as EntitiesResolver;
use Minds\Core\Reports\V2\Controllers\VerdictController;
use Minds\Core\Reports\V2\Repositories\ReportRepository;
use Minds\Core\Reports\V2\Services\ActionService;
use Minds\Core\Reports\V2\Services\ReportService;

class Provider extends DiProvider
{
    /**
     * @return void
     */
    public function register(): void
    {
        $this->di->bind(ReportController::class, function (Di $di): ReportController {
            return new ReportController(
                service: $di->get(ReportService::class)
            );
        }, ['factory' => true]);

        $this->di->bind(VerdictController::class, function (Di $di): VerdictController {
            return new VerdictController(
                service: $di->get(ReportService::class)
            );
        }, ['factory' => true]);

        $this->di->bind(ReportService::class, function (Di $di): ReportService {
            return new ReportService(
                repository: $di->get(ReportRepository::class),
                actionService: $di->get(ActionService::class),
                config: $di->get(Config::class),
                logger: $di->get('Logger')
            );
        }, ['factory' => true]);

        $this->di->bind(ActionService::class, function (Di $di): ActionService {
            return new ActionService(
                entitiesBuilder: $di->get(EntitiesBuilder::class),
                entitiesResolver: $di->get(EntitiesResolver::class),
                commentManager: $di->get('Comments\Manager'),
                channelsBanManager: $di->get('Channels\Ban'),
                deleteAction: new Delete()
            );
        }, ['factory' => true]);

        $this->di->bind(ReportRepository::class, function (Di $di): ReportRepository {
            return new ReportRepository(
                mysqlHandler: $di->get(MySQLClient::class),
                config: $di->get(Config::class),
                logger: $di->get('Logger')
            );
        }, ['factory' => true]);
    }
}
