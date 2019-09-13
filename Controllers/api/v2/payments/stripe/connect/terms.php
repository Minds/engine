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

class terms implements Interfaces\Api
{
    public function get($pages)
    {
        return Factory::response([]);
    }

    public function post($pages)
    {
        return Factory::response([]);
    }

    public function put($pages)
    {
        $user = Session::getLoggedInUser();
        $connectManager = new Stripe\Connect\Manager();
        $account = $connectManager->getByUser($user);
        $account->setIp($_SERVER['HTTP_X_FORWARDED_FOR']);
        $connectManager->acceptTos($account);
        return Factory::response([]);
    }

    public function delete($pages)
    {
        return Factory::response([]);
    }
}
