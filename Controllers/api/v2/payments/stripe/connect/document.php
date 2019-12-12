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

class document implements Interfaces\Api
{
    public function get($pages)
    {
        return Factory::response([]);
    }

    public function post($pages)
    {
        $documentType = $pages[0] ?? null;

        if (!$documentType) {
            return Factory::response([
                'status' => 'error',
                'message' => '/:documentType must be provided',
            ]);
        }

        $user = Session::getLoggedInUser();
        $connectManager = new Stripe\Connect\Manager();
        $account = $connectManager->getByUser($user);
        $fp = fopen($_FILES['file']['tmp_name'], 'r');
        $connectManager->addDocument($account, $fp, $documentType);
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
