<?php
declare(strict_types=1);

namespace Minds\Core\Payments\V2;

use Minds\Core\Di\Di;
use Minds\Core\Di\ImmutableException;
use Minds\Core\Di\Provider as DiProvider;
use Minds\Core\Payments\GiftCards\Manager as GiftCardsManager;
use Minds\Core\Payments\Stripe\PaymentMethods\Manager as StripePaymentMethodsManager;
use Minds\Core\Payments\V2\Controllers\PaymentMethodsController;
use Minds\Core\Payments\V2\PaymentMethods\Manager as PaymentMethodsManager;

class Provider extends DiProvider
{
    /**
     * @return void
     * @throws ImmutableException
     */
    public function register(): void
    {
        $this->di->bind(
            Manager::class,
            function (Di $di): Manager {
                return new Manager();
            },
            ['factory' => false]
        );

        $this->di->bind(
            Repository::class,
            function (Di $di): Repository {
                return new Repository();
            },
            ['factory' => true]
        );

        $this->di->bind(
            PaymentMethodsManager::class,
            function (Di $di): PaymentMethodsManager {
                return new PaymentMethodsManager(
                    new StripePaymentMethodsManager(),
                    $di->get(GiftCardsManager::class),
                    $di->get('Logger'),
                );
            },
            ['factory' => true]
        );

        $this->di->bind(
            PaymentMethodsController::class,
            function (Di $di): PaymentMethodsController {
                return new PaymentMethodsController(
                    $di->get(PaymentMethodsManager::class),
                    $di->get('Logger'),
                );
            },
            ['factory' => true]
        );
    }
}
