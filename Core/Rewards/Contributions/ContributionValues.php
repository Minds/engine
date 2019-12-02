<?php
namespace Minds\Core\Rewards\Contributions;

class ContributionValues
{
    public static $multipliers = [
        'comments' => 2,
        'reminds' => 4,
        'votes' => 1,
        'subscribers' => 4,
        'referrals' => 1,
        'referrals_welcome' => 1,
        'checkin' => 2,
        'jury_duty' => 25,
        'onchain_tx' => 10,
    ];

    /**
     * Public export of values
     * @return array
     */
    public static function export()
    {
        return static::$multipliers;
    }
}
