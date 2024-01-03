<?php
namespace Minds\Core\Notifications\PostSubscriptions;

use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Core\Di\Provider as DiProvider;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Log\Logger;
use Minds\Core\Data\MySQL\Client as MySQLClient;
use Minds\Core\Notifications\PostSubscriptions\Controllers\PostSubscriptionsController;
use Minds\Core\Notifications\PostSubscriptions\Repositories\PostSubscriptionsRepository;
use Minds\Core\Notifications\PostSubscriptions\Services\PostSubscriptionsService;

class Provider extends DiProvider
{
    public function register()
    {
        $this->di->bind(PostSubscriptionsController::class, function (Di $di): PostSubscriptionsController {
            return new PostSubscriptionsController(
                postSubscriptionsService: $di->get(PostSubscriptionsService::class),
                entitiesBuilder: $di->get(EntitiesBuilder::class),
            );
        });

        $this->di->bind(PostSubscriptionsService::class, function (Di $di): PostSubscriptionsService {
            return new PostSubscriptionsService(
                repository: $di->get(PostSubscriptionsRepository::class)
            );
        });

        $this->di->bind(PostSubscriptionsRepository::class, function (Di $di): PostSubscriptionsRepository {
            return new PostSubscriptionsRepository(
                mysqlHandler: $di->get(MySQLClient::class),
                config: $di->get(Config::class),
                logger: $di->get('Logger'),
            );
        });
    }
}
