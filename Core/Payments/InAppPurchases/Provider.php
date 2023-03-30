<?php
declare(strict_types=1);

namespace Minds\Core\Payments\InAppPurchases;

use Minds\Core\Di\Di;
use Minds\Core\Di\Provider as DiProvider;
use Minds\Core\Payments\InAppPurchases\Clients\InAppPurchasesClientFactory;

class Provider extends DiProvider
{
    /**
     * @return void
     * @throws \Minds\Core\Di\ImmutableException
     */
    public function register(): void
    {
        $this->di->bind(Manager::class, function (Di $di): Manager {
            return new Manager();
        }, ['factory' => true]);
        $this->di->bind(InAppPurchasesClientFactory::class, function (Di $di): InAppPurchasesClientFactory {
            return new InAppPurchasesClientFactory();
        }, ['factory' => true]);
        $this->di->bind(Controller::class, function (Di $di): Controller {
            return new Controller();
        }, ['factory' => true]);
    }
}
