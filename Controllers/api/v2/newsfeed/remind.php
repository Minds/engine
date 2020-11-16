<?php

namespace Minds\Controllers\api\v2\newsfeed;

use Minds\Api\Factory;
use Minds\Interfaces;
use Minds\Core\Di\Di;
use Minds\Core\Feeds\Activity;
use Minds\Core\Session;
use Minds\Entities;

class remind implements Interfaces\Api
{
    public function get($pages)
    {
        return Factory::response([]);
    }

    public function post($pages)
    {
        /** @var Activity\Manager */
        $manager = Di::_()->get('Feeds\Activity\Manager');

        /** @var Entities\User */
        $user = Session::getLoggedInUser();

        if (!$user) {
            return Factory::response([
                'status' => 'error',
                'message' => 'You must be logged in',
            ]);
        }

        // This is a tempoary endpoint that will be deprecated shortly
        $remind = Di::_()->get('EntitiesBuilder')->single($pages[0] ?? null);

        if (!$remind) {
            return Factory::response([
                'status' => 'error',
                'message' => 'Remind not found',
            ]);
        }

        // Mobile users expect remind to remind the original post still
        if ($remind->isRemind() || $remind->isQuotedPost()) {
            $remind = $remind->getRemind();
        }

        if (!Di::_()->get('Security\ACL')->interact($remind, $user)) {
            return Factory::response([
                'status' => 'error',
                'message' => 'You can not interact with this post',
            ]);
        }

        $remindIntent = new Activity\RemindIntent();
        $remindIntent->setGuid($remind->getGuid())
            ->setOwnerGuid($remind->getOwnerGuid())
            ->setQuotedPost(true); // All posts from this endpoint are quoted posts

        $activity = new Entities\Activity();
        $activity->setRemind($remindIntent);

        if (isset($_POST['message'])) {
            $activity->setMessage(rawurldecode($_POST['message']));
        }

        if ($manager->add($activity)) {
            return Factory::response([
                'guid' => $activity->getGuid(),
            ]);
        }

        return Factory::response([
            'status' => 'error',
            'message' => 'There was an unkown error',
        ]);
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
