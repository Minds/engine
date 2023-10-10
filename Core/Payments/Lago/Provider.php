<?php
declare(strict_types=1);

namespace Minds\Core\Payments\Lago;

use Minds\Core\Di\Di;
use Minds\Core\Di\ImmutableException;
use Minds\Core\Di\Provider as DiProvider;
use Minds\Core\Payments\Lago\Clients\CustomersClient;
use Minds\Core\Payments\Lago\Controllers\Controller;

class Provider extends DiProvider
{
    /**
     * @return void
     * @throws ImmutableException
     */
    public function register(): void
    {
        $this->di->bind(WebhookController::class, function (Di $di): WebhookController {
            return new WebhookController();
        });

        $this->di->bind(Controller::class, function (Di $di): Controller {
            return new Controller(
                $di->get(Manager::class),
                $di->get('Logger')
            );
        });

        $this->di->bind(Manager::class, function (Di $di): Manager {
            return new Manager(
                $di->get(CustomersClient::class)
            );
        });

        (new Clients\ClientsProvider())->register();
    }
}
