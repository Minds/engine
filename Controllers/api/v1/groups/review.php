<?php
/**
 * Minds Group API
 * Admin Queue Reviews endpoint
 */
namespace Minds\Controllers\api\v1\groups;

use Minds\Api\Exportable;
use Minds\Core;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Groups\V2\Membership\Manager;
use Minds\Entities;
use Minds\Interfaces;
use Minds\Api\Factory;
use Minds\Core\Di\Di;
use Minds\Exceptions\NotFoundException;

class review implements Interfaces\Api
{
    public function __construct(
        protected ?Manager $membershipManager = null,
        protected ?EntitiesBuilder $entitiesBuilder = null
    ) {
        $this->membershipManager = Di::_()->get(Core\Groups\V2\Membership\Manager::class);
        $this->entitiesBuilder = Di::_()->get('EntitiesBuilder');
    }

    public function get($pages)
    {
        Factory::isLoggedIn();

        /** @var Entities\Group */
        $group = $this->entitiesBuilder->single($pages[0]);
        $user = Core\Session::getLoggedInUser();

        if (!$group) {
            return Factory::response([
                'status' => 'error',
                'message' => 'Group not found'
            ]);
        }

        try {
            $membership = $this->membershipManager->getMembership($group, $user);
        } catch (NotFoundException $e) {
            return Factory::response([
                'status' => 'error',
                'message' => 'You are not a group member'
            ]);
        }

        if (!$membership->isOwner() && !$membership->isModerator()) {
            return Factory::response([
                'status' => 'error',
                'message' => 'You don\'t have enough permissions'
            ]);
        }

        /** @var Core\Groups\Feeds $feeds */
        $feeds = Core\Di\Di::_()->get('Groups\Feeds');
        $feeds->setGroup($group);

        $count = $feeds->count();

        if (isset($pages[1]) && $pages[1] == 'count') {
            return Factory::response([
                'adminqueue:count' => $count
            ]);
        }

        $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 12;
        $offset = isset($_GET['offset']) ? $_GET['offset'] : '';

        $result = $feeds->getAll([ 'limit' => $limit, 'offset' => $offset ], $loadNext);

        return Factory::response([
            'activity' => Exportable::_($result),
            'adminqueue:count' => $count,
            'load-next' => $loadNext,
        ]);
    }

    public function post($pages)
    {
        return Factory::response([]);
    }

    public function put($pages)
    {
        Factory::isLoggedIn();

        /** @var Entities\Group */
        $group = $this->entitiesBuilder->single($pages[0]);
        $activity = $this->entitiesBuilder->single($pages[1]);
        $user = Core\Session::getLoggedInUser();

        try {
            $membership = $this->membershipManager->getMembership($group, $user);
        } catch (NotFoundException $e) {
            return Factory::response([
                'status' => 'error',
                'message' => 'You are not a group member'
            ]);
        }

        if (!$membership->isOwner() && !$membership->isModerator()) {
            return Factory::response([
                'status' => 'error',
                'message' => 'You don\'t have enough permissions'
            ]);
        }

        /** @var Core\Groups\Feeds $feeds */
        $feeds = Core\Di\Di::_()->get('Groups\Feeds');

        $done = $feeds->setGroup($group)
            ->approve($activity);

        if ($done) {
            return Factory::response([]);
        } else {
            return Factory::response([
                'status' => 'error',
                'message' => 'Cannot approve activity'
            ]);
        }
    }

    public function delete($pages)
    {
        Factory::isLoggedIn();

        $group = Entities\Factory::build($pages[0]);
        $activity = Entities\Factory::build($pages[1]);
        $user = Core\Session::getLoggedInUser();

        if (!$group->isOwner($user) && !$group->isModerator($user)) {
            return Factory::response([
                'status' => 'error',
                'message' => 'You don\'t have enough permissions'
            ]);
        }

        /** @var Core\Groups\Feeds $feeds */
        $feeds = Core\Di\Di::_()->get('Groups\Feeds');

        $done = $feeds->setGroup($group)
            ->reject($activity);

        if ($done) {
            return Factory::response([]);
        } else {
            return Factory::response([
                'status' => 'error',
                'message' => 'Cannot approve activity'
            ]);
        }
    }
}
