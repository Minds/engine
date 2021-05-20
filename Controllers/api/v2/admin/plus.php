<?php

namespace Minds\Controllers\api\v2\admin;

use Minds\Api\Factory;
use Minds\Core\Pro\Manager;
use Minds\Core\Plus\Subscription as PlusSubscription;
use Minds\Entities\User as UserEntity;
use Minds\Interfaces;
use Minds\Core\Di\Di;
use Minds\Core\Security\ACL;

class plus implements Interfaces\Api, Interfaces\ApiAdminPam
{
    /**
     * Equivalent to HTTP GET method
     * @param array $pages
     * @return mixed|null
     */
    public function get($pages)
    {
        return Factory::response([]);
    }

    /**
     * Equivalent to HTTP POST method
     * @param array $pages
     * @return mixed|null
     */
    public function post($pages)
    {
        return Factory::response([]);
    }

    /**
     * Equivalent to HTTP PUT method
     * @param array $pages
     * @return mixed|null
     */
    public function put($pages)
    {
        $userGuid = $pages[0] ?? false;
        $action = $pages[1] ?? false;
        $timespan = $pages[2] ?? false;
 
        if (!$action || !$userGuid) {
            return Factory::response([
                'status' => 'error',
                'message' => 'Invalid parameters. Expected guid, action and optional timespan',
            ]);
        }

        $target = Di::_()->get('EntitiesBuilder')->single($pages[0], [
            'cache' => false,
        ]);

        if (!$target || !$target->guid || $target->getType() !== 'user') {
            return Factory::response([
                'status' => 'error',
                'message' => 'Invalid target',
            ]);
        }

        if ($action === 'make') {
            $relativeTimespan = '+30 days';

            switch ($timespan) {
                case 'month':
                    $relativeTimespan = '+1 month';
                    break;
                case 'year':
                    $relativeTimespan = '+1 year';
                    break;
                case 'life':
                    $relativeTimespan = '+100 years';
                    break;
                default:
                    throw new \Exception('Invalid timespan');
            }

            /** @var Manager $manager */
            $target->setPlusExpires(
                strtotime($relativeTimespan, time())
            );
        }

        if ($action === 'remove') {
            $target->setPlusExpires(time());
            try {
                (new PlusSubscription())
                    ->setUser($target)
                    ->cancel();
            } catch (\Exception $e) {
                Di::_()->get('Logger')->error($e);
            }
        }

        $isAllowed = ACL::_()->setIgnore(true); // store previous state.

        $success = $target->save();

        ACL::_()->setIgnore($isAllowed); // set back to previous state.

        if (!$success) {
            return Factory::response([
                'status' => 'error',
                'message' => 'Error disabling Plus',
            ]);
        }

        return Factory::response([]);
    }

    /**
     * Equivalent to HTTP DELETE method
     * @param array $pages
     * @return mixed|null
     */
    public function delete($pages)
    {
        return Factory::response([]);
    }
}
