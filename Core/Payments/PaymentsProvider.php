<?php
/**
 * Minds Payments Provider
 */

namespace Minds\Core\Payments;

use Minds\Core;
use Minds\Core\Data;
use Minds\Core\Di\Provider;

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

        $this->di->bind('Payments\Points', function ($di) {
            return new Points\Manager();
        });

        $this->di->bind('StripePayments', function ($di) {
            $config = $di->get('Config');
            return new Stripe\Stripe($di->get('Config'));
        }, ['useFactory'=>true]);

        $this->di->bind('StripeSDK', function ($di) {
            $config = $di->get('Config');
            \Stripe\Stripe::setApiKey($config->get('payments')['stripe']['api_key']);
            \Stripe\Stripe::setApiVersion('2020-03-02');
        }, ['useFactory'=>true]);

        $this->di->bind('Stripe\Connect\Manager', function ($di) {
            return new Stripe\Connect\Manager();
        }, ['useFactory'=>true]);

        $this->di->bind('Stripe\Intents\Manager', function ($di) {
            return new Stripe\Intents\Manager();
        }, ['useFactory'=>true]);

        $this->di->bind('Stripe\Transactions\Manager', function ($di) {
            return new Stripe\Transactions\Manager();
        }, ['useFactory'=>true]);
    }
}
