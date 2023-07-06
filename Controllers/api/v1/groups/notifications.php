<?php
/**
 * Minds Group API
 * Notification-related endpoints
 */
namespace Minds\Controllers\api\v1\groups;

use Minds\Core;
use Minds\Core\Groups\V2\Membership\Manager;
use Minds\Core\Session;
use Minds\Interfaces;
use Minds\Api\Factory;
use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;
use Minds\Entities\Factory as EntitiesFactory;
use Minds\Entities\Group;
use Minds\Exceptions\GroupOperationException;
use Minds\Exceptions\NotFoundException;

class notifications implements Interfaces\Api
{
    public function __construct(
        protected ?Manager $membershipManager = null,
        protected ?EntitiesBuilder $entitiesBuilder = null
    ) {
        $this->membershipManager = Di::_()->get(Manager::class);
        $this->entitiesBuilder = Di::_()->get('EntitiesBuilder');
    }

    public function get($pages)
    {
        Factory::isLoggedIn();

        /** @var Group */
        $group = $this->entitiesBuilder->single($pages[0]);
        $user = Session::getLoggedInUser();

        try {
            $membership = $this->membershipManager->getMembership($group, $user);
        } catch (NotFoundException $e) {
            return Factory::response([
                'status' => 'error',
                'message' => 'You are not a group member'
            ]);
        }

        if (!$membership->isMember()) {
            return Factory::response([
                'is:muted' => false
            ]);
        }

        $notifications = (new Core\Groups\Notifications)
          ->setGroup($group);

        return Factory::response([
            'is:muted' => $notifications->isMuted($user)
        ]);
    }

    public function post($pages)
    {
        Factory::isLoggedIn();

        /** @var Group */
        $group = $this->entitiesBuilder->single($pages[0]);
        $user = Session::getLoggedInUser();

        try {
            $membership = $this->membershipManager->getMembership($group, $user);
        } catch (NotFoundException $e) {
            return Factory::response([
                'status' => 'error',
                'message' => 'You are not a group member'
            ]);
        }

        if (!$membership->isMember()) {
            return Factory::response([]);
        }

        $notifications = (new Core\Groups\Notifications)
          ->setGroup($group);

        try {
            switch ($pages[1]) {
                case 'mute':
                    $notifications->mute($user);
                    return Factory::response([
                        'is:muted' => true
                    ]);
                    break;
                case 'unmute':
                    $notifications->unmute($user);
                    return Factory::response([
                        'is:muted' => false
                    ]);
            }
        } catch (GroupOperationException $e) {
            return Factory::response([
                'is:muted' => false,
                'error' => $e->getMessage()
            ]);
        }

        return Factory::response([]);
    }

    public function put($pages)
    {
        return Factory::response([]);
    }

    public function delete($pages)
    {
        return Factory::response([]);
    }
}
