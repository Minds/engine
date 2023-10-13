<?php
declare(strict_types=1);

namespace Minds\Core\Payments\Lago;

use Minds\Core\Di\Di;
use Minds\Core\Di\ImmutableException;
use Minds\Core\Di\Provider as DiProvider;
use Minds\Core\Payments\Lago\Clients\CustomersClient;
use Minds\Core\Payments\Lago\Clients\SubscriptionsClient;
use Minds\Core\Payments\Lago\Controllers\Controller;
use Minds\Core\Payments\Lago\Controllers\CustomersController;
use Minds\Core\Payments\Lago\Controllers\SubscriptionsController;
use Minds\Core\Payments\Lago\Types\InputTypesProvider;

class Provider extends DiProvider
{
    /**
     * @return void
     * @throws ImmutableException
     */
    public function register(): void
    {
        ###### Controllers ######
        $this->di->bind(WebhookController::class, function (Di $di): WebhookController {
            return new WebhookController();
        });

        $this->di->bind(Controller::class, function (Di $di): Controller {
            return new Controller(
                $di->get(Manager::class),
                $di->get('Logger')
            );
        });

        $this->di->bind(CustomersController::class, function (Di $di): CustomersController {
            return new CustomersController(
                $di->get(Manager::class),
                $di->get('Logger')
            );
        });

        $this->di->bind(SubscriptionsController::class, function (Di $di): SubscriptionsController {
            return new SubscriptionsController(
                $di->get(Manager::class),
                $di->get('Logger')
            );
        });

        ###### Manager ######
        $this->di->bind(Manager::class, function (Di $di): Manager {
            return new Manager(
                $di->get(CustomersClient::class),
                $di->get(SubscriptionsClient::class)
            );
        });

        ###### Clients ######
        (new Clients\ClientsProvider())->register();

        ###### GraphQL Input Types Factories ######
        (new InputTypesProvider())->register();
    }
}
