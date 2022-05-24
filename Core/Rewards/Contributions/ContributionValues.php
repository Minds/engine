<?php
namespace Minds\Core\Rewards\Contributions;

class ContributionValues
{
    public static $multipliers = [
        //'comments' => 2,
        //'reminds' => 4,
        'votes' => 1,
        // 'downvotes' => -1,
        //'subscribers' => 4,
        //'referrals' => 10,
        //'referrals_welcome' => 1,
        //'checkin' => 2,
        //'jury_duty' => 25,
        //'onchain_tx' => 10,
    ];

    /**
     * Returns the multiplier based on metricKey
     * @param string $metricKey
     * @return int
     */
    public static function metricKeyToMultiplier(string $metricKey): int
    {
        switch ($metricKey) {
            // case 'comment':
            //     return static::$multipliers['comments'];
            // case 'remind':
            //     return static::$multipliers['reminds'];
            case 'vote:up':
                return static::$multipliers['votes'];
            // case 'vote:down':
            //     return static::$multipliers['downvotes'];
            // case 'subscribers':
            //     return static::$multipliers['subscribe'];
            default:
                return 0;
        }
    }

    /**
     * Public export of values
     * @return array
     */
    public static function export()
    {
        return static::$multipliers;
    }
}
