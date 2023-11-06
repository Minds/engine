<?php

namespace Minds\Controllers\api\v2\newsfeed;

use Minds\Api\Factory;
use Minds\Core;
use Minds\Core\Di\Di;
use Minds\Core\Entities\Actions\Save;
use Minds\Core\Groups\V2\Membership\Manager;
use Minds\Core\EntitiesBuilder;
use Minds\Entities;
use Minds\Entities\Activity;
use Minds\Entities\Group;
use Minds\Exceptions\NotFoundException;
use Minds\Interfaces;

class pin implements Interfaces\Api
{
    public function __construct(
        protected ?Manager $membershipManager = null,
        protected ?EntitiesBuilder $entitiesBuilder = null,
        protected ?Save $save = null,
    ) {
        $this->membershipManager = Di::_()->get(Manager::class);
        $this->entitiesBuilder = Di::_()->get('EntitiesBuilder');
        $this->save = new Save();
    }

    public function get($pages)
    {
        return Factory::response([]);
    }

    public function post($pages)
    {
        if (!isset($pages[0])) {
            return Factory::response(['status' => 'error', 'message' => 'You must send an Activity GUID']);
        }

        /** @var Activity $activity */
        $activity = Entities\Factory::build($pages[0]);

        $user = Core\Session::getLoggedinUser();

        if ($activity->container_guid != $user->guid) {
            /** @var Group */
            $group = $this->entitiesBuilder->single($activity->container_guid);

            try {
                $membership = $this->membershipManager->getMembership($group, $user);
            } catch (NotFoundException $e) {
                return Factory::response([
                    'error' => 'No group membership was found'
                ]);
            }

            if ($membership->isModerator() || $membership->isOwner()) {
                $group->addPinned($activity->guid);
                $this->save->setEntity($group)->withMutatedAttributes(['pinned_posts'])->save();
            } else {
                return Factory::response([
                    'status' => 'error',
                    'message' => 'You do not not have permission to pin to this group',
                ]);
            }
        } else {
            $user->addPinned($activity->guid);
            $this->save->setEntity($user)->withMutatedAttributes(['pinned_posts'])->save();
        }

        return Factory::response([]);
    }

    public function put($pages)
    {
        return Factory::response([]);
    }

    public function delete($pages)
    {
        if (!isset($pages[0])) {
            return Factory::response(['status' => 'error', 'message' => 'You must send an Activity GUID']);
        }
        /** @var Activity $activity */
        $activity = Entities\Factory::build($pages[0]);
        $user = Core\Session::getLoggedinUser();

        if ($activity->container_guid != $user->guid) {
            /** @var Group */
            $group = $this->entitiesBuilder->single($activity->container_guid);

            try {
                $membership = $this->membershipManager->getMembership($group, $user);
            } catch (NotFoundException $e) {
                return Factory::response([
                    'error' => 'No group membership was found'
                ]);
            }
        
            if ($membership->isModerator() || $membership->isOwner()) {
                $group->removePinned($activity->guid);
                $this->save->setEntity($group)->withMutatedAttributes(['pinned_posts'])->save();
            } else {
                return Factory::response([
                    'status' => 'error',
                    'message' => 'You do not not have permission to pin to this group',
                ]);
            }
        } else {
            $user->removePinned($activity->guid);
            $this->save->setEntity($user)->withMutatedAttributes(['pinned_posts'])->save();
        }

        return Factory::response([]);
    }
}
