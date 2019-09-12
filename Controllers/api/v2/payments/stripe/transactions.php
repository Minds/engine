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

class transactions implements Interfaces\Api
{
    public function get($pages)
    {
        $user = Session::getLoggedInUser();

        $connectManager = new Stripe\Connect\Manager();

        try {
            $account = $connectManager->getByUser($user);
        } catch (\Exception $e) {
            return Factory::response([
                'status' => 'error',
                'message' => 'There was an error returning the usd account',
            ]);
        }
 
        $transactionsManger = new Stripe\Transactions\Manager();
        $transactions = $transactionsManger->getByAccount($account);

        return Factory::response([
            'transactions' => Factory::exportable($transactions),
        ]);
    }

    public function post($pages)
    {
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
