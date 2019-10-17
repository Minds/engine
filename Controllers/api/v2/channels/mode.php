<?php

/**
 * Channel Mode and reindexing api
 * Changing channel mode requires a significant reindexing
 * This api provides a rate limited way of setting a channel mode
 * And updating all of a user's entities and comments in a background job
 *
 * @author Brian Hatchet
 */

namespace Minds\Controllers\api\v2\channels;

use Minds\Api\Factory;
use Minds\Core\Di\Di;
use Minds\Interfaces;
use Minds\Common\ChannelMode;
use Minds\Core\Session;
use Minds\Core\Entities\Actions;
use Minds\Core\Queue\Client as Queue;

class mode implements Interfaces\Api
{
    public function get($pages) : bool
    {
        if (!is_numeric($pages[0])) {
            header('X-Minds-Exception: user guid required');
            http_response_code(400);
            return Factory::response(['status' => 'error', 'message' => 'user guid required']);
        }

        /** @var EntitiesBuilder $entitiesBuilder */
        $entitiesBuilder = Di::_()->get('EntitiesBuilder');
        $user = $entitiesBuilder->single($pages[0]);

        if (!$user || $user->getType() !== 'user') {
            header('X-Minds-Exception: user guid required');
            http_response_code(400);
            return Factory::response(['status' => 'error', 'message' => 'user guid required']);
        }
        return Factory::response([
            'status' => 'success',
            'guid' => $user->getGuid(),
            'mode' => $user->getMode(),
            'indexedAt' => $user->getIndexedAt()
        ]);
    }

    public function post($pages) : bool
    {
    }


    public function put($pages) : bool
    {
        if (!is_numeric($pages[0])) {
            header('X-Minds-Exception: user guid required');
            http_response_code(400);
            return Factory::response(['status' => 'error', 'message' => 'user guid required']);
        }

        $channelMode = $pages[1];
        if (!is_numeric($channelMode) && !ChannelMode::isValid($channelMode)) {
            header('X-Minds-Exception: channel mode required');
            http_response_code(400);
            return Factory::response(['status' => 'error', 'message' => 'channel mode required']);
        }

        if (!is_numeric($pages[1])) {
            header('X-Minds-Exception: user guid required');
            http_response_code(400);
            return Factory::response(['status' => 'error', 'message' => 'user guid required']);
        }
        
        /** @var EntitiesBuilder $entitiesBuilder */
        $entitiesBuilder = Di::_()->get('EntitiesBuilder');
        $channel = $entitiesBuilder->single($pages[0]);

        if (!$channel || $channel->getType() !== 'user') {
            header('X-Minds-Exception: user guid required');
            http_response_code(400);
            return Factory::response(['status' => 'error', 'message' => 'user guid required']);
        }

        $currentUser = Session::getLoggedInUser();
        if (
            !$currentUser
            || !$currentUser->isAdmin()
            || $currentUser->getGuid() !== $channel->getGuid()
        ) {
            header('X-Minds-Exception: only owners and admins can change channel modes');
            http_response_code(401);
            return Factory::response(['status' => 'error', 'message' => 'only owners and admins can change channel modes']);
        }

        if (!$channel->canBeIndexed()) {
            header('X-Minds-Exception: Too many change mode requests. Try again later');
            http_response_code(429);
            return Factory::response(['status' => 'error', 'message' => 'Too many change mode requests. Try again later']);
        }

        $channel->setMode($channelMode);
        $channel->setIndexedAt(time());
        $save = (new Actions\Save())
            ->setEntity($channel)
            ->save();

        Queue::build()
            ->setQueue('ChannelDeferredOps')
            ->send([
                "user_guid" => $channel->getGuid(),
                "type" => 'updateOwnerObject'
            ]);

        return Factory::response([
            'status' => 'success'
        ]);
    }

    public function delete($pages) : bool
    {
        return Factory::response([]);
    }
}
