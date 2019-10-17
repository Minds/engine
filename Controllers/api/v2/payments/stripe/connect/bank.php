<?php
/**
 *
 */
namespace Minds\Controllers\api\v2\payments\stripe\connect;

use Minds\Api\Factory;
use Minds\Common\Cookie;
use Minds\Core\Di\Di;
use Minds\Core\Config;
use Minds\Core\Session;
use Minds\Interfaces;
use Minds\Core\Payments\Stripe;

class bank implements Interfaces\Api
{
    public function get($pages)
    {
        $user = Session::getLoggedInUser();

        $connectManager = new Stripe\Connect\Manager();

        return Factory::response([
        ]);
    }

    public function post($pages)
    {
        $user = Session::getLoggedInUser();
        $connectManager = new Stripe\Connect\Manager();

        $account = $connectManager->getByUser($user);
        if (!$account) {
            return Factory::response([
                'status' => 'error',
                'message' => 'You must have a USD account to add a bank account',
            ]);
        }

        $account->setAccountNumber($_POST['accountNumber'])
            ->setCountry($_POST['country'])
            ->setRoutingNumber($_POST['routingNumber']);

        try {
            $connectManager->addBankAccount($account);
        } catch (\Exception $e) {
            return Factory::response([
                'status' => 'error',
                'message' => $e->getMessage(),
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
