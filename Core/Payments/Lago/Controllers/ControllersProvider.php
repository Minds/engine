<?php
declare(strict_types=1);

namespace Minds\Core\Payments\Lago\Controllers;

use Minds\Core\Di\Di;
use Minds\Core\Di\ImmutableException;
use Minds\Core\Di\Provider;
use Minds\Core\Payments\Lago\Services\CustomersService;
use Minds\Core\Payments\Lago\Services\InvoicesService;
use Minds\Core\Payments\Lago\Services\SubscriptionsService;

class ControllersProvider extends Provider
{
    /**
     * @return void
     * @throws ImmutableException
     */
    public function register(): void
    {
        $this->di->bind(
            CustomersController::class,
            function (Di $di): CustomersController {
                return new CustomersController(
                    $di->get(CustomersService::class),
                    $di->get('Logger')
                );
            }
        );

        $this->di->bind(
            SubscriptionsController::class,
            function (Di $di): SubscriptionsController {
                return new SubscriptionsController(
                    $di->get(SubscriptionsService::class),
                    $di->get('Logger')
                );
            }
        );

        $this->di->bind(
            InvoicesController::class,
            function (Di $di): InvoicesController {
                return new InvoicesController(
                    $di->get(InvoicesService::class),
                    $di->get('Logger')
                );
            }
        );
    }
}
