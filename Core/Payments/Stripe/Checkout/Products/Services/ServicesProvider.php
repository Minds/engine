<?php
declare(strict_types=1);

namespace Minds\Core\Payments\Stripe\Checkout\Products\Services;

use Minds\Core\Di\Provider;
use Minds\Core\Payments\Stripe\Instances\ProductInstance;
use Minds\Core\Payments\Stripe\Instances\ProductPriceInstance;

class ServicesProvider extends Provider
{
    public function register(): void
    {
        $this->di->bind(
            ProductPriceService::class,
            fn (): ProductPriceService => new ProductPriceService(
                priceInstance: new ProductPriceInstance()
            )
        );

        $this->di->bind(
            ProductService::class,
            fn (): ProductService => new ProductService(
                productInstance: new ProductInstance()
            )
        );
    }
}
