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

class photoid implements Interfaces\Api
{
    public function get($pages)
    {
        return Factory::response([]);
    }

    public function post($pages)
    {
        $user = Session::getLoggedInUser();
        $connectManager = new Stripe\Connect\Manager();
        $account = $connectManager->getByUser($user);
        $fp = fopen($_FILES['file']['tmp_name'], 'r');
        $connectManager->addPhotoId($account, $fp);
        return Factory::response([ 'account_id' => $account->getId() ]);
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
