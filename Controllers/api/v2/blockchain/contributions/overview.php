<?php

/**
 * Site Contributions Overview
 *
 * @author emi
 */

namespace Minds\Controllers\api\v2\blockchain\contributions;

use Minds\Core\Analytics\UserStates\RewardFactor;
use Minds\Core\Session;
use Minds\Interfaces;
use Minds\Api\Factory;
use Minds\Core\Rewards\Contributions;

class overview implements Interfaces\Api
{
    /**
     * Equivalent to HTTP GET method
     * @param  array $pages
     * @return mixed|null
     */
    public function get($pages)
    {
        if (Factory::isLoggedIn()) {
            $user = Session::getLoggedinUser();
            $overview = new Contributions\Overview();
            $overview
                ->setUser($user)
                ->calculate();

            $rewardFactor = RewardFactor::getForUserState($user->getUserState());
            $contributionValues = array_map(function ($value) use ($rewardFactor) {
                return $value * $rewardFactor;
            }, Contributions\ContributionValues::$multipliers);

            $response = [
                'nextPayout' => $overview->getNextPayout(),
                'currentReward' => $overview->getCurrentReward(),
                'yourContribution' => $overview->getYourContribution(),
                'totalNetworkContribution' => $overview->getTotalNetworkContribution(),
                'yourShare' => $overview->getYourShare(),
                'yourRewardFactor' => $overview->getYourRewardFactor(),
                'contributionValues' => $contributionValues,
                'rewardFactors' => RewardFactor::$values
            ];
            return Factory::response($response);
        }
    }

    /**
     * Equivalent to HTTP POST method
     * @param  array $pages
     * @return mixed|null
     */
    public function post($pages)
    {
        return Factory::response([]);
    }

    /**
     * Equivalent to HTTP PUT method
     * @param  array $pages
     * @return mixed|null
     */
    public function put($pages)
    {
        return Factory::response([]);
    }

    /**
     * Equivalent to HTTP DELETE method
     * @param  array $pages
     * @return mixed|null
     */
    public function delete($pages)
    {
        return Factory::response([]);
    }
}
