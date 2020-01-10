<?php
namespace Minds\Helpers\Campaigns;

use Minds\Core\Di\Di;

class Referrals
{
    /**
     * Registers a cookie for the referral step
     * @param  string  $username
     * @return void
     */
    public static function register($username): void
    {
        Di::_()->get('Referrals\Cookie')->create();
    }
}
