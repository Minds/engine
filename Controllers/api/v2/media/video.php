<?php
/**
 * Minds Video Controller
 *
 * @author Mark Harding
 */

namespace Minds\Controllers\api\v2\media;

use Minds\Api\Factory;
use Minds\Core\Di\Di;
use Minds\Core\Media\Proxy\Download;
use Minds\Core\Media\Proxy\Resize;
use Minds\Interfaces;

class video implements Interfaces\Api, Interfaces\ApiIgnorePam
{
    /**
     * Equivalent to HTTP GET method
     * @param  array $pages
     * @return mixed|null
     */
    public function get($pages)
    {
        $videoManager = Di::_()->get('Media\Video\Manager');

        $video = $videoManager->get($pages[0]);

        Factory::response([
            'entity' => $video->export(),
            'sources' => Factory::exportable($videoManager->getSources($video)),
            'poster' => $video->getIconUrl(),
        ]);
    }

    /**
     * Equivalent to HTTP POST method
     * @param  array $pages
     * @return mixed|null
     */
    public function post($pages)
    {
        http_response_code(501);
        exit;
    }

    /**
     * Equivalent to HTTP PUT method
     * @param  array $pages
     * @return mixed|null
     */
    public function put($pages)
    {
        http_response_code(501);
        exit;
    }

    /**
     * Equivalent to HTTP DELETE method
     * @param  array $pages
     * @return mixed|null
     */
    public function delete($pages)
    {
        http_response_code(501);
        exit;
    }
}
