<?php
declare(strict_types=1);

namespace Minds\Core\Payments\Lago\Services;

use Minds\Core\Di\Di;
use Minds\Core\Di\ImmutableException;
use Minds\Core\Di\Provider as DiProvider;
use Minds\Core\Payments\Lago\Clients\CustomersClient;
use Minds\Core\Payments\Lago\Clients\InvoicesClient;
use Minds\Core\Payments\Lago\Clients\SubscriptionsClient;

class ServicesProvider extends DiProvider
{
    /**
     * @return void
     * @throws ImmutableException
     */
    public function register(): void
    {
        $this->di->bind(SubscriptionsService::class, function (Di $di): SubscriptionsService {
            return new SubscriptionsService(
                $di->get(SubscriptionsClient::class)
            );
        });
        $this->di->bind(CustomersService::class, function (Di $di): CustomersService {
            return new CustomersService(
                $di->get(CustomersClient::class),
                $di->get('Logger')
            );
        });
        $this->di->bind(
            InvoicesService::class,
            function (Di $di): InvoicesService {
                return new InvoicesService(
                    $di->get(InvoicesClient::class),
                    $di->get('Logger')
                );
            }
        );
    }
}
