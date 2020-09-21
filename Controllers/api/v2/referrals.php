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
        $limit = isset($_GET['limit']) ? $_GET['limit'] : 12;
        $offset = isset($_GET['offset']) ? $_GET['offset'] : "";

        if (!$referrer_guid) {
            return Factory::response([
                'status' => 'error',
                'message' => 'Referrer guid must be supplied.'
            ]);
        }

        $manager = Di::_()->get('Referrals\Manager');
        $opts = [
            'referrer_guid'=>$referrer_guid,
            'limit'=>$limit,
            'offset'=>$offset
        ];

        $referrals = $manager->getList($opts);

        if (!$referrals) {
            return Factory::response([
                'status' => 'error',
                'message' => 'You have no referrals'
            ]);
        }

        $response['referrals'] = Factory::exportable(array_values($referrals->toArray()));
        $response['load-next'] = (string) $referrals->getPagingToken();

        return Factory::response($response);
    }

    // Not implemented
    // Note: New referrals are added when prospect registers for Minds (in `Core/Events/Hooks/Register.php`)
    public function post($pages)
    {
    }

    // Notify a prospect to urge them to join the rewards program
    // Note: referrals are updated when prospect joins rewards (in `Core/Rewards/Delegates/ReferralDelegate.php`)
    public function put($pages)
    {
        $referrer_guid = Core\Session::getLoggedInUser()->guid;

        if (!$referrer_guid) {
            return Factory::response([
                'status' => 'error',
                'message' => 'You must be logged in to trigger a notification',
            ]);
        }

        if (!isset($pages[0])) {
            return Factory::response([
                'status' => 'error',
                'message' => 'Prospect guid is required to trigger a notification',
            ]);
        }

        $prospect_guid = $pages[0];
        $referral = new Referral;
        $referral->setReferrerGuid($referrer_guid)
            ->setProspectGuid($prospect_guid);

        $manager = Di::_()->get('Referrals\Manager');
        if (!$manager->ping($referral)) {
            return Factory::response([
                'status' => 'error',
                'done' => false,
            ]);
        }

        return Factory::response([
            'status' => 'success',
            'done' => true,
        ]);
    }

    // Not implemented
    public function delete($pages)
    {
    }
}
