<?php
/**
 * Minds Payments Provider
 */

namespace Minds\Core\Payments;

use Minds\Core;
use Minds\Core\Data;
use Minds\Core\Di\Provider;

use Braintree_ClientToken;
use Braintree_Configuration;
use Braintree_Transaction;
use Braintree_TransactionSearch;
use Braintree_MerchantAccount;

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

        //

        $this->di->bind('Payments\Points', function ($di) {
            return new Points\Manager();
        });

        //

        $this->di->bind('BraintreePayments', function ($di) {
            $config = $di->get('Config');
            $braintree = new Braintree\Braintree(new Braintree_Configuration(), $di->get('Config'));
            /*$braintree->setConfig([
              'environment' => $config->payments['braintree']['default']['environment'] ?: 'sandbox',
              'merchant_id' => $config->payments['braintree']['default']['merchant_id'],
              'master_merchant_id' => $config->payments['braintree']['default']['master_merchant_id'],
              'public_key' => $config->payments['braintree']['default']['public_key'],
              'private_key' => $config->payments['braintree']['default']['private_key']
              ]);*/
            return $braintree;
        }, ['useFactory'=>true]);
        $this->di->bind('StripePayments', function ($di) {
            $config = $di->get('Config');
            return new Stripe\Stripe($di->get('Config'));
        }, ['useFactory'=>true]);
    }
}
