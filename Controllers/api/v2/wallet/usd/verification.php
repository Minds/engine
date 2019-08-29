<?php
/**
 * USD Wallet Controller
 *
 * @version 1
 * @author Mark Harding
 */
namespace Minds\Controllers\api\v2\wallet\usd;

use Minds\Core;
use Minds\Helpers;
use Minds\Interfaces;
use Minds\Api\Factory;
use Minds\Core\Payments;
use Minds\Entities;

class verification implements Interfaces\Api
{
    /**
     * @param array $pages
     */
    public function get($pages)
    {
        return Factory::response([]);
    }

    /**
     * @param array $pages
     */
    public function post($pages)
    {
        Factory::isLoggedIn();
        $response = [];

        try {
            $stripe = Core\Di\Di::_()->get('StripePayments');
            $stripe->verifyMerchant(Core\Session::getLoggedInUser()->getMerchant()['id'], $_FILES['file']);
        } catch (\Exception $e) {
            $response['status'] = "error";
            $response['message'] = $e->getMessage();
        }
           
        return Factory::response($response);
    }

    /**
     * @param array $pages
     */
    public function put($pages)
    {
        return Factory::response([]);
    }

    /**
     * @param array $pages
     */
    public function delete($pages)
    {
        return Factory::response([]);
    }
}
