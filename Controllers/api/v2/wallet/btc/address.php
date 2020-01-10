<?php
/**
 * BTC Wallet Controller
 *
 * @version 1
 * @author Mark Harding
 */
namespace Minds\Controllers\api\v2\wallet\btc;

use Minds\Core;
use Minds\Core\Entities\Actions;
use Minds\Helpers;
use Minds\Interfaces;
use Minds\Api\Factory;
use Minds\Core\Payments;
use Minds\Entities;

class address implements Interfaces\Api
{
    /**
     * @param array $pages
     */
    public function get($pages)
    {
        Factory::isLoggedIn();

        $response = [
            'address' => Core\Session::getLoggedInUser()->getBtcAddress(),
        ];
    
        return Factory::response($response);
    }

    /**
     * @param array $pages
     */
    public function post($pages)
    {
        Factory::isLoggedIn();

        $response = [];

        $user = Core\Session::getLoggedInUser();
        $save = new Actions\Save();
            
        $user->setBtcAddress($_POST['address']);
        $save->setEntity($user)
            ->save();

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
