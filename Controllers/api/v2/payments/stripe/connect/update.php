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

class update implements Interfaces\Api
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

        if ($_POST['phone'] ?? null) {
            $account->setPhoneNumber($_POST['phone']);
        }

        if ($_POST['id_number'] ?? null) {
            $account->setPersonalIdNumber($_POST['id_number']);
        }

        try {
            $connectManager->update($account);
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
