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

        $manager = Di::_()->get('Referrals\Manager');
        $opts = ['referrer_guid'=>Core\Session::getLoggedInUserGuid()];

        $referrals = $manager->getList($opts);


        // OJMTODO: confirm what is the outcome for request with no referrals 
        // OJMTODO: incorporate no referral case into UI
        if (!$referrals) {
            return Factory::response(array(
                'status' => 'error',
                'message' => 'You have no referrals'
            ));
        }

        $response['referrals'] = Factory::exportable(array_values($referrals->toArray()));

        // OJMTODO: remove all this when done testing
        // OJMTODO: learn syntax for objects/arrays more betterer
        $tempProspect1 = (object) [
            "guid" => "988145006634078224",
            "verified" => true,
            "username" => "oldprospector",
            "name" => "oldprospector",
            "icontime" => "1560987887"
          ];
        
        $tempRef1 = (object) [
            'referrer_guid' => '987892327202689039',
            'state' => 'complete',
            'score' => 10,
            'register_timestamp' => "1560857128000",
            'join_timestamp' => "1560867128000",
            'prospect' => $tempProspect1
        ];

        $tempProspect2 = (object) [
            "guid" => "988145006634077224",
            "verified" => true,
            "username" => "doge",
            "name" => "doge",
            "icontime" => "1560987887"
          ];
        
        $tempRef2 = (object) [
            'referrer_guid' => '987892327202689039',
            'state' => 'complete',
            'score' => 10,
            'register_timestamp' => "1440837128000",
            'join_timestamp' => "1550867108000",
            'prospect' => $tempProspect2
        ];

        array_push($response['referrals'], $tempRef1);
        array_push($response['referrals'], $tempRef2);


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
