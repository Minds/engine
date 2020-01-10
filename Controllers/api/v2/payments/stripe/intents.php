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

class intents implements Interfaces\Api
{
    public function get($pages)
    {
        return Factory::response([
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
                $intent = $intentManager->get($_POST['intent_id']);

                $customersManager = new Stripe\Customers\Manager();
                $customer = $customersManager->getFromUserGuid($user->getGuid());

                if (!$customer) {
                    $customer = new Stripe\Customers\Customer;
                    $customer->setUser($user)
                        ->setUserGuid($user->getGuid());
                }

                $customer->setPaymentMethod($intent->getPaymentMethod());

                $customersManager->updatePaymentMethod($customer);

                var_dump($customer); exit;

            break;
        }

        return Factory::response([]);
    }

    public function put($pages)
    {
        $user = Session::getLoggedInUser();

        $intent = new Stripe\Intents\SetupIntent();

        $intentManager = new Stripe\Intents\Manager();
        $intent = $intentManager->add($intent);
 
        
        return Factory::response([
            'intent' => $intent->export(),
        ]);
    }

    public function delete($pages)
    {
        $user = Session::getLoggedInUser();
        $user->setCanary(false);
        $user->save();
        return Factory::response([]);
    }
}
