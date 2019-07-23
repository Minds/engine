<?php
/**
 * 
 */
namespace Minds\Controllers\api\v2\payments\stripe;

use Minds\Api\Factory;
use Minds\Common\Cookie;
use Minds\Core\Di\Di;
use Minds\Core\Config;
use Minds\Core\Session;
use Minds\Interfaces;
use Minds\Core\Payments\Stripe;

class paymentmethods implements Interfaces\Api
{

    public function get($pages)
    {
        $user = Session::getLoggedInUser();
 
        $paymentMethodsManager = new Stripe\PaymentMethods\Manager();
        $paymentMethods = $paymentMethodsManager->getList([
            'limit' => 12,
            'user_guid' => $user->getGuid(),
        ]);

        return Factory::response([
            'paymentmethods' => Factory::exportable($paymentMethods),
        ]);
    }

    public function post($pages)
    {
        $user = Session::getLoggedInUser();
        switch ($pages[0]) {
            case 'apply':
                $intent = new Stripe\Intents\SetupIntent();
                $intent->setId($_POST['intent_id']);

                $intentManager = new Stripe\Intents\Manager();
                $paymentMethodsManager = new Stripe\PaymentMethods\Manager();
                $customersManager = new Stripe\Customers\Manager();

                // Get the intent
                $intent = $intentManager->get($_POST['intent_id']);

                // Grab our customerId
                $customer = $customersManager->getFromUserGuid($user->getGuid());

                // Build a payment method
                $paymentMethod = new Stripe\PaymentMethods\PaymentMethod();
                $paymentMethod->setId($intent->getPaymentMethod())
                    ->setUserGuid($user->getGuid());

                if ($customer) {
                    $paymentMethod->setCustomerId($customer->getId());
                }

                // Save the payment method
                $paymentMethodsManager->add($paymentMethod);

            break;
        }
        return Factory::response([]);
    }

    public function put($pages)
    {
        return Factory::response([]);
    }

    public function delete($pages)
    {
        $paymentMethodsManager = new Stripe\PaymentMethods\Manager();
        $paymentMethodsManager->delete($pages[0]);
        return Factory::response([]);
    }
    
}



