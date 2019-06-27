<?php
/**
 * Referrals API
 *
 * @version 1
 * @author Olivia Madrid
 */
namespace Minds\Controllers\api\v2;

use Minds\Core;
use Minds\Core\Referrals\Referral;
use Minds\Interfaces;
use Minds\Api\Factory;
use Minds\Core\Di\Di;


class referrals implements Interfaces\Api
{
    /**
     * Returns a list of referrals
     * @param array $pages
     */
    public function get($pages)
    {
        $response = [];

        $referrer_guid = isset($pages[0]) ? $pages[0] : Core\Session::getLoggedInUser()->guid;
        $limit = isset($_GET['limit']) ? $_GET['limit'] : 3;
        $offset = isset($_GET['offset']) ? $_GET['offset'] : "";

        $manager = Di::_()->get('Referrals\Manager');
        $opts = [
            'referrer_guid'=>$referrer_guid,
            'limit'=>$limit,
            'offset'=>$offset
        ];

        $referrals = $manager->getList($opts);

        if (!$referrals) {
            return Factory::response(array(
                'status' => 'error',
                'message' => 'You have no referrals'
            ));
        }

        $response['referrals'] = Factory::exportable(array_values($referrals->toArray()));
        $response['load-next'] = (string) $referrals->getPagingToken();

        return Factory::response($response);
    }
    
    // Not implemented - new referrals are added in Core/Events/Hooks/Register.php (when prospect registers for Minds)
    public function post($pages)
    {
    }

    // Not implemented - referrals are updated in ReferralDelegate.php (when prospect joins rewards)
    public function put($pages)
    {
    }

    // Not implemented
    public function delete($pages)
    {
    }
}
