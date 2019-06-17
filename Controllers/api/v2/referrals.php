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


class referrals implements Interfaces\Api
{
    /**
     * Returns a list of referrals
     * @param array $pages
     */
    public function get($pages)
    {
        $response = [];

        $referral = new Referral();
        $referral->setReferrerGuid(Core\Session::getLoggedInUserGuid());
   
        $manager = Di::_()->get('Referrals\Manager');
        $referrals = $manager->getList($referral);

        // OJMTODO: confirm what is the outcome for request with no referrals 
        // OJMTODO: incorporate no referral case into UI
        if (!$referrals) {
            return Factory::response(array(
                'status' => 'error',
                'message' => 'You have no referrals'
            ));
        }

        // OJMQ: hydrate here? or frontend as needed?
        $response['referrals'] = Factory::exportable(array_values($referrals->toArray()));

        return Factory::response($response);
    }
    

    // Not implemented
    public function post($pages)
    {
    }

    // Not implemented
    public function put($pages)
    {
    }

    // Not implemented
    public function delete($pages)
    {
    }
}
