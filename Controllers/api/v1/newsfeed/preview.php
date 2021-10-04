<?php
/**
 * Minds Newsfeed API
 *
 * @version 1
 * @author Mark Harding
 */
namespace Minds\Controllers\api\v1\newsfeed;

use Minds\Core;
use Minds\Entities;
use Minds\Interfaces;
use Minds\Api\Factory;

class preview implements Interfaces\Api
{
    /**
     * Returns a preview of a url
     * @param array $pages
     *
     * API:: /v1/newsfeed/preview
     */
    public function get($pages)
    {
        $url = $_GET['url'];
        $meta = Core\Di\Di::_()->get('Feeds\Activity\RichEmbed\Manager')->getRichEmbed($url);
        return Factory::response($meta);
    }

    public function post($pages)
    {
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
