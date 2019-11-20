<?php
/**
 * Trigger video transcode manually.
 * @author Ben Hayward
 */
namespace Minds\Controllers\api\v2\admin;

use Minds\Api\Factory;
use Minds\Interfaces;
use Minds\Core\Media\Video\Manager;
use Minds\Entities;

class transcode implements Interfaces\Api, Interfaces\ApiAdminPam
{
    /**
     * Not yet implemented GET.
     * @param  array $pages
     * @return mixed|null
     */
    public function get($pages)
    {
        return Factory::response([]);
    }

    /**
     * Manually requeue a video for transcoding.
     * @param  array $pages - video guid must be supplied.
     * @return mixed|null
     */
    public function post($pages)
    {
        $guid = intval($_POST['guid']);

        if (!$guid) {
            return Factory::response(['status' => 'error', 'message' => 'You must send a GUID.']);
        }
        
        $entity = Entities\EntitiesFactory::build($guid);
        
        if (!$entity) {
            return Factory::response(['status' => 'error', 'message' => 'Entity not found.']);
        }

        $user = $entity->getOwnerEntity();

        if (!$user) {
            return Factory::response(['status' => 'error', 'message' => 'User not found.']);
        }
        
        $videoManager = new Manager();

        if (!$videoManager->queueTranscoding($guid, $user->isPro())) {
            return Factory::response(['status' => 'error', 'message' => 'Failed to add video to transcoding queue.']);
        }

        return Factory::response(['status' => 'success']);
    }

    /**
     * Not yet implemented PUT.
     * @param  array $pages
     * @return mixed|null
     */
    public function put($pages)
    {
        return Factory::response([]);
    }

    /**
     * Not yet implemented DELETE.
     * @param  array $pages
     * @return mixed|null
     */
    public function delete($pages)
    {
        return Factory::response([]);
    }
}
