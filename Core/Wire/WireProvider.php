<?php

namespace Minds\Core\Wire;

use Minds\Core\Di\Di;
use Minds\Core\Di\Provider;
use Minds\Core\Payments\Stripe\StripeApiKeyConfig;
use Minds\Core\Payments\Stripe\Subscriptions\Services\SubscriptionsService;

/**
 * Wire Providers
 */
class WireProvider extends Provider
{
    /**
     * Registers providers onto DI
     * @return null
     */
    public function register()
    {
        $this->di->bind('Wire', function ($di) {
        }, ['useFactory' => true]);

        $this->di->bind('Wire\Manager', function (Di $di): Manager {
            return new Manager(
                giftCardsManager: $di->get(\Minds\Core\Payments\GiftCards\Manager::class),
                stripeSubscriptionsService: $di->get(SubscriptionsService::class),
                customersManager: $di->get('Stripe\Customers\ManagerV2'),
                stripeApiKeyConfig: $di->get(StripeApiKeyConfig::class),
            );
        }, ['useFactory' => true]);

        $this->di->bind('Wire\Subscriptions\Manager', function (Di $di): Subscriptions\Manager {
            return new Subscriptions\Manager(
                giftCardsManager: $di->get(\Minds\Core\Payments\GiftCards\Manager::class)
            );
        }, ['useFactory' => true]);

        $this->di->bind('Wire\Repository', function ($di) {
            return new Repository(Di::_()->get('Database\Cassandra\Cql'), Di::_()->get('Config'));
        }, ['useFactory' => false]);

        $this->di->bind('Wire\Counter', function ($di) {
            return new Counter;
        }, ['useFactory' => true]);

        $this->di->bind('Wire\Thresholds', function ($di) {
            return new Thresholds();
        }, ['useFactory' => true]);

        $this->di->bind('Wire\Sums', function ($di) {
            return new Sums();
        }, ['useFactory' => false]);

        $this->di->bind('Wire\Leaderboard', function ($di) {
            return new Leaderboard();
        }, ['useFactory' => false]);
    }
}
