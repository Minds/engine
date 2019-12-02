<?php
/**
 * RequestHydrationDelegate
 * @author edgebal
 */

namespace Minds\Core\Rewards\Withdraw\Delegates;

use Exception;
use Minds\Core\Rewards\Withdraw\Request;
use Minds\Entities\User;

class RequestHydrationDelegate
{
    /**
     * @param Request $request
     * @return Request
     * @throws Exception
     */
    public function hydrate(Request $request)
    {
        $userGuid = $request->getUserGuid();

        if (!$userGuid) {
            return $request;
        }

        try {
            $user = new User($userGuid);
        } catch (Exception $exception) {
            $user = null;
        }

        return $request
            ->setUser($user);
    }

    public function hydrateForAdmin(Request $request)
    {
        if (!$request->getUser()) {
            $request = $this->hydrate($request);

            if (!$request->getUser()) {
                return $request;
            }
        }

        $referrerGuid = $request->getUser()->referrer;

        if (!$referrerGuid) {
            return $request;
        }

        try {
            $user = new User($referrerGuid);
        } catch (Exception $exception) {
            // Faux user in case of banned/deleted accounts
            $user = new User();
            $user->guid = $referrerGuid;
            $user->username = $referrerGuid;
        }

        return $request
            ->setReferrer($user);
    }
}
