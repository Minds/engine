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
use Minds\Core\Entities\Actions\Save;
use Minds\Core\EntitiesBuilder;
use Minds\Entities\Factory as EntitiesFactory;
use Minds\Entities\Group;
use Minds\Entities\User;

use Minds\Exceptions\GroupOperationException;
use Minds\Exceptions\NotFoundException;

class management implements Interfaces\Api
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
        return Factory::response([]);
    }

    public function post($pages)
    {
        return Factory::response([]);
    }

    public function put($pages)
    {
        Factory::isLoggedIn();

        /** @var \Minds\Entities\Group $group */
        $group = EntitiesFactory::build($pages[0]);
        $actor = Session::getLoggedInUser();

        if (!$group || !$group->getGuid()) {
            return Factory::response([
                'done' => false,
                'error' => 'No group'
            ]);
        }

        if (!isset($pages[1]) || !$pages[1] || !ctype_alnum($pages[1])) {
            return Factory::response([
                'done' => false,
                'error' => 'Invalid user'
            ]);
        }

        $member = $this->entitiesBuilder->single($pages[1]);

        if (!$member || !$member->getGuid()) {
            return Factory::response([
                'done' => false,
                'error' => 'User not found'
            ]);
        }

        try {
            $membership = $this->membershipManager->getMembership($group, $actor);
        } catch (NotFoundException $e) {
            return Factory::response([
                'error' => 'No membership was found'
            ]);
        }

        $management = (new Core\Groups\Management())
          ->setGroup($group)
          ->setActor($actor);

        if (!$membership->isOwner()) {
            return Factory::response([
                'done' => false,
                'status' => 'error',
                'error' => 'Only owners can make these changes',
            ]);
        }

        try {
            if (isset($pages[2]) && $pages[2] == 'moderator') {
                $granted = $management->grantModerator($member);
            } else {
                $granted = $management->grantOwner($member);
            }
        } catch (GroupOperationException $e) {
            return Factory::response([
                'done' => false,
                'error' => $e->getMessage()
            ]);
        }

        if ($granted && isset($pages[2]) && $pages[2] === 'owner') {
            if ((string)$actor->guid !== (string)$group->owner_guid) {
                return Factory::response([
                    'status' => 'error',
                    'message' => 'Only a group owner can pass ownership'
                ]);
            }

            $saved = false;
            $group->setOwnerObj($member);
            try {
                $saved = (new Save())->setEntity($group)->save();
            } catch (\Exception $e) {
                error_log($e->getMessage());
            }
            return Factory::response(['done' => $saved]);
        }

        return Factory::response([
            'done' => $granted
        ]);
    }

    public function delete($pages)
    {
        Factory::isLoggedIn();

        /** @var Group */
        $group = EntitiesFactory::build($pages[0]);
        $actor = Session::getLoggedInUser();

        if (!$group || !$group->getGuid()) {
            return Factory::response([
                'done' => false,
                'error' => 'No group'
            ]);
        }

        if (!isset($pages[1]) || !$pages[1] || !ctype_alnum($pages[1])) {
            return Factory::response([
                'done' => false,
                'error' => 'Invalid user'
            ]);
        }

        $member = $this->entitiesBuilder->single($pages[1]);

        if (!$member || !$member->getGuid()) {
            return Factory::response([
                'done' => false,
                'error' => 'User not found'
            ]);
        }

        try {
            $membership = $this->membershipManager->getMembership($group, $actor);
        } catch (NotFoundException $e) {
            return Factory::response([
                'error' => 'No membership was found'
            ]);
        }

        $management = (new Core\Groups\Management())
          ->setGroup($group)
          ->setActor($actor);

        if (!$membership->isOwner()) {
            return Factory::response([
                'done' => false,
                'status' => 'error',
                'error' => 'Only owners can make these changes',
            ]);
        }

        try {
            if (isset($pages[2]) && $pages[2] == 'moderator') {
                $revoked = $management->revokeModerator($member);
            } else {
                $revoked = $management->revokeOwner($member);
            }
        } catch (GroupOperationException $e) {
            return Factory::response([
                'done' => false,
                'error' => $e->getMessage()
            ]);
        }

        return Factory::response([
            'done' => $revoked
        ]);
    }
}
