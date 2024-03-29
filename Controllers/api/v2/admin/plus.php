<?php

namespace Minds\Controllers\api\v2\admin;

use Minds\Api\Factory;
use Minds\Core\Pro\Manager;
use Minds\Core\Plus\Subscription as PlusSubscription;
use Minds\Entities\User as UserEntity;
use Minds\Interfaces;
use Minds\Core\Di\Di;
use Minds\Core\Entities\Actions\Save;
use Minds\Core\Security\ACL;
use Minds\Core\Log\Logger;

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
        // $logger = Di::_()->get('Logger');
        $logger = new Logger('Minds', [
            'minLogLevel' => Logger::DEBUG,
        ]);

        $logger->warning('AdminPlus | endpoint being hit');
     
        $userGuid = $pages[0] ?? false;
        $action = $pages[1] ?? false;
        $timespan = $pages[2] ?? false;

        $logger->warning('AdminPlus | userGuid: '.$userGuid);
        $logger->warning('AdminPlus | timespan: '.$timespan);
        $logger->warning('AdminPlus | action: '.$action);

        if (!$action || !$userGuid) {
            return Factory::response([
                'status' => 'error',
                'message' => 'Invalid parameters. Expected guid, action and optional timespan',
            ]);
        }

        $target = Di::_()->get('EntitiesBuilder')->single($pages[0], [
            'cache' => false,
        ]);

        $logger->warning('AdminPlus | target username: '.$target->getUsername());

        // Manually flush the cache.
        $channelsManager = Di::_()->get('Channels\Manager');
        $channelsManager->flushCache($target);

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
            $logger->warning('AdminPlus | plus_expires on target is currently '.$target->getPlusExpires());
            $logger->warning('AdminPlus | setting plus expires to '.time());

            $target->setPlusExpires(time());

            $logger->warning('AdminPlus | plus_expires set to '.$target->getPlusExpires());

            try {
                (new PlusSubscription())
                    ->setUser($target)
                    ->cancel();

                $logger->warning('AdminPlus | cancelled subscription');
            } catch (\Exception $e) {
                $logger->warning('AdminPlus | caught error cancelling subscription');
                Di::_()->get('Logger')->error($e);
            }
        }

        $isAllowed = ACL::_()->setIgnore(true); // store previous state.

        $logger->warning('AdminPlus | saving...');
        
        $success = (new Save())->setEntity($target)->withMutatedAttributes(['plus_expires'])->save();
        ACL::_()->setIgnore($isAllowed); // set back to previous state.

        if (!$success) {
            return Factory::response([
                'status' => 'error',
                'message' => 'Error disabling Plus',
            ]);
        }

        $logger->warning('AdminPlus | saved, new plus_expires: '.$target->getPlusExpires());

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
