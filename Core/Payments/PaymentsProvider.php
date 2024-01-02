<?php
/**
 * Minds Payments Provider
 */

namespace Minds\Core\Payments;

use Minds\Core\Di\Di;
use Minds\Core\Di\Provider;
use Minds\Core\Payments\Stripe\StripeApiKeyConfig;
use Minds\Core\Payments\Stripe\StripeClient;
use Minds\Core\Session;
use Minds\Entities\User;

class PaymentsProvider extends Provider
{
    public function register()
    {
        $this->di->bind('Payments\Manager', function ($di) {
            return new Manager();
        });

        $this->di->bind('Payments\Repository', function ($di) {
            return new Repository();
        }, [ 'useFactory' => true ]);

        $this->di->bind('StripePayments', function ($di) {
            $config = $di->get('Config');
            return new Stripe\Stripe($di->get('Config'));
        }, ['useFactory'=>true]);

        $this->di->bind('StripeSDK', function ($di, $args) {
            $stripeApiKeyConfig = $di->get(StripeApiKeyConfig::class);
            $apiKey = $stripeApiKeyConfig->get(
                isset($args['user']) && $args['user'] instanceof User ?
                    $args['user'] :
                    null
            );
            \Stripe\Stripe::setApiKey($apiKey);
            \Stripe\Stripe::setApiVersion($args['api_version'] ?? '2020-03-02');
        }, ['useFactory' => false]);

        $this->di->bind(StripeApiKeyConfig::class, function ($di) {
            return new StripeApiKeyConfig();
        });

        $this->di->bind(StripeClient::class, function ($di, $args) {
            $loggedInUser = Session::getLoggedinUser();
            return (new StripeClient())->withUser($loggedInUser ?? null, [
                'stripe_version' => $args['stripe_version'] ?? '2020-03-02',
            ]);
        });

        /**
         * Connect
         */
        $this->di->bind('Stripe\Connect\Manager', function ($di) {
            return new Stripe\Connect\Manager();
        }, ['useFactory'=>true]);

        $this->di->bind('Stripe\Connect\ManagerV2', function ($di) {
            return new Stripe\Connect\ManagerV2();
        }, ['useFactory'=>true]);

        $this->di->bind('Stripe\Connect\Controller', function ($di) {
            return new Stripe\Connect\Controller();
        }, ['useFactory'=>true]);

        /**
         * Intents
         */
        $this->di->bind('Stripe\Intents\Manager', function ($di) {
            return new Stripe\Intents\Manager();
        }, ['useFactory'=>true]);

        /**
         * Transactions
         */
        $this->di->bind('Stripe\Transactions\Manager', function ($di) {
            return new Stripe\Transactions\Manager();
        }, ['useFactory'=>true]);

        /**
         * Payment methods
         */
        $this->di->bind('Stripe\PaymentMethods\Manager', function ($di) {
            return new Stripe\PaymentMethods\Manager();
        }, ['useFactory'=>true]);

        /**
         * Customers
         */
        $this->di->bind('Stripe\Customers\Manager', function ($di) {
            return new Stripe\Customers\Manager();
        }, ['useFactory'=>true]);
        $this->di->bind('Stripe\Customers\ManagerV2', function ($di) {
            return new Stripe\Customers\ManagerV2();
        }, ['useFactory'=>true]);

        $this->di->bind(
            Stripe\Checkout\Manager::class,
            fn (Di $di): Stripe\Checkout\Manager => new Stripe\Checkout\Manager(),
            ['useFactory'=>true]
        );
        /**
         * Checkout
         */
        $this->di->bind('Stripe\Checkout\Manager', function ($di) {
            return $di->get(Stripe\Checkout\Manager::class);
        }, ['useFactory'=>true]);
        $this->di->bind('Stripe\Checkout\Controller', function ($di) {
            return new Stripe\Checkout\Controller();
        }, ['useFactory'=>true]);
    }
}
