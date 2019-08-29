<?php
/**
 *
 */
namespace Minds\Controllers\api\v2\payments\stripe\paymentmethods;

use Minds\Api\Factory;
use Minds\Common\Cookie;
use Minds\Core\Di\Di;
use Minds\Core\Config;
use Minds\Core\Session;
use Minds\Interfaces;
use Minds\Core\Payments\Stripe;

class apply implements Interfaces\Api
{
    public function get($pages)
    {
        return Factory::response([]);
    }

    public function post($pages)
    {
        $user = Session::getLoggedInUser();
        
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
        try {
            $paymentMethodsManager->add($paymentMethod);
        } catch (\Exception $e) {
            return Factory::response([
                'status' => 'error',
                'message' => 'Sorry, there was an error. Please try again',
            ]);
        }

        return Factory::response([]);
    }

    public function put($pages)
    {
        return Factory::response([]);
    }

    public function delete($pages)
    {
        return Factory::response([]);
    }
}
