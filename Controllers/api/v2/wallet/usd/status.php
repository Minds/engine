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

class status implements Interfaces\Api
{
    /**
     * @param array $pages
     */
    public function get($pages)
    {
        Factory::isLoggedIn();

        $merchants = Core\Di\Di::_()->get('Monetization\Merchants');
        $merchants->setUser(Core\Sandbox::user(Core\Session::getLoggedInUser(), 'merchant'));

        $isMerchant = (bool) $merchants->getId();
        $canBecomeMerchant = !$merchants->isBanned();

        return Factory::response([
            'isMerchant' => $isMerchant,
            'canBecomeMerchant' => $canBecomeMerchant,
        ]);
    }

    /**
     * @param array $pages
     */
    public function post($pages)
    {
        return Factory::response([]);
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
