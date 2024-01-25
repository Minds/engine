<?php
declare(strict_types=1);

namespace Minds\Core\Payments\Stripe\Keys;

use Minds\Core\Config\Config;
use Minds\Core\Data\MySQL\Client;
use Minds\Core\Di\Di;
use Minds\Core\Di\ImmutableException;
use Minds\Core\Di\Provider as DiProvider;
use Minds\Core\Payments\Stripe\Keys\Controllers\StripeKeysController;
use Minds\Core\Security\Vault\VaultTransitService;

class Provider extends DiProvider
{
    /**
     * @return void
     * @throws ImmutableException
     */
    public function register(): void
    {
        $this->di->bind(StripeKeysRepository::class, function (Di $di): StripeKeysRepository {
            return new StripeKeysRepository(
                $di->get(Client::class),
                $di->get(Config::class),
                $di->get('Logger'),
            );
        });

        $this->di->bind(StripeKeysService::class, function (Di $di): StripeKeysService {
            return new StripeKeysService(
                $di->get(StripeKeysRepository::class),
                $di->get(VaultTransitService::class),
            );
        });

        $this->di->bind(StripeKeysController::class, function (Di $di): StripeKeysController {
            return new StripeKeysController(
                service: $di->get(StripeKeysService::class),
            );
        });
    }
}
