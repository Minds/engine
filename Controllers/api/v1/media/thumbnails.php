<?php
/**
 * Minds Media API
 *
 * @version 1
 * @author Emi Balbuena
 */
namespace Minds\Controllers\api\v1\media;

use Minds\Api\Factory;
use Minds\Common;
use Minds\Core;
use Minds\Core\Di\Di;
use Minds\Core\Features\Manager as FeaturesManager;
use Minds\Entities;
use Minds\Interfaces;

class thumbnails implements Interfaces\Api, Interfaces\ApiIgnorePam
{
    /**
     * Forwards to or passes-thru the the entity's thumbnail
     * @param array $pages
     *
     * API:: /v1/media/thumbnails/:guid/:size
     */
    public function get($pages)
    {
        if (!$pages[0]) {
            exit;
        }

        $guid = $pages[0];

        Core\Security\ACL::$ignore = true;

        $size = isset($pages[1]) ? $pages[1] : null;

        $last_cache = isset($pages[2]) ? $pages[2] : time();

        $entity = Entities\Factory::build($guid);

        if (!$entity) {
            return Factory::response([
                'status' => 'error',
                'message' => 'Entity not found'
            ]);
        }

        $featuresManager = new FeaturesManager();

        if ($entity->access_id !== Common\Access::PUBLIC && $featuresManager->has('cdn-jwt')) {
            error_log("{$_SERVER['REQUEST_URI']} was hit, and should not have been");

            return Factory::response([
                'status' => 'error',
                'message' => 'This endpoint has been deprecated. Please use fs/v1/thumbnail',
            ]);
        }

        $etag = $last_cache . $guid;
        if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && trim($_SERVER['HTTP_IF_NONE_MATCH']) == $etag) {
            header("HTTP/1.1 304 Not Modified");
            exit;
        }

        $thumbnail = Di::_()->get('Media\Thumbnails')->get($entity, $size);

        if ($thumbnail instanceof \ElggFile) {
            $thumbnail->open('read');
            $contents = $thumbnail->read();

            header('Content-type: image/jpeg');
            header('Expires: ' . date('r', strtotime('today + 6 months')), true);
            header('Pragma: public');
            header('Cache-Control: public');
            header('Content-Length: ' . strlen($contents));

            $chunks = str_split($contents, 1024);
            foreach ($chunks as $chunk) {
                echo $chunk;
            }
        } elseif (is_string($thumbnail)) {
            \forward($thumbnail);
        }

        exit;
    }

    /**
     * POST Method
     */
    public function post($pages)
    {
        return Factory::response([]);
    }

    /**
     * PUT Method
     */
    public function put($pages)
    {
        return Factory::response([]);
    }

    /**
     * DELETE Method
     */
    public function delete($pages)
    {
        return Factory::response([]);
    }
}
