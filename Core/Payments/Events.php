<?php
namespace Minds\Core\Payments;

use Cassandra\Varint;
use Minds\Core;
use Minds\Core\Di\Di;
use Minds\Core\Email\Campaigns;
use Minds\Core\Events\Dispatcher;
use Minds\Core\Events\Event;
use Minds\Core\Payments;
use Minds\Core\Session;
use Minds\Entities\User;

/**
 * Minds Payments Events
 */
class Events
{
    public function register()
    {

        // Legacy, called from Core/Wire/Webhook

        Dispatcher::register('wire-payment-email', 'object', function ($event) {
            $campaign = new Campaigns\WirePayment;
            $params = $event->getParameters();
            $user = $params['user'];
            if (!$user) {
                return false;
            }

            $campaign->setUser($user);

            if ($params['charged']) {
                $bankAccount = $params['bankAccount'];
                $dateOfDispatch = $params['dateOfDispatch'];
                if (!$bankAccount || !$dateOfDispatch) {
                    return false;
                }
                $campaign->setBankAccount($bankAccount)
                    ->setDateOfDispatch($dateOfDispatch);
            } else {
                $amount = $params['amount'];
                $unit = $params['unit'];
                if (!$amount || !$unit) {
                    return false;
                }

                $campaign->setAmount($amount)
                    ->setDescription($unit);
            }

            $campaign->send();


            return $event->setResponse(true);
        });
    }
}
