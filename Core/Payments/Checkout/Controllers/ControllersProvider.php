<?php
declare(strict_types=1);

namespace Minds\Core\Payments\Checkout\Controllers;

use Minds\Core\Di\Di;
use Minds\Core\Di\ImmutableException;
use Minds\Core\Di\Provider;
use Minds\Core\Payments\Checkout\Services\CheckoutContentService;
use Minds\Core\Payments\Checkout\Services\CheckoutService;

class ControllersProvider extends Provider
{
    /**
     * @return void
     * @throws ImmutableException
     */
    public function register(): void
    {
        $this->di->bind(
            CheckoutContentController::class,
            fn (Di $di): CheckoutContentController => new CheckoutContentController(
                checkoutContentService: $di->get(CheckoutContentService::class),
            ),
        );
        $this->di->bind(
            CheckoutGqlController::class,
            fn (Di $di): CheckoutGqlController => new CheckoutGqlController(
                checkoutService: $di->get(CheckoutService::class),
            ),
        );
        $this->di->bind(
            CheckoutPsrController::class,
            fn (Di $di): CheckoutPsrController => new CheckoutPsrController(
                checkoutService: $di->get(CheckoutService::class)
            )
        );
    }
}
