<?php
/**
 * Minds Boost Api endpoint
 * @version 1
 * @author Mark Harding
 * @deprecated
 */
namespace Minds\Controllers\api\v2\boost;

use Minds\Interfaces;
use Minds\Api\Factory;

class peer implements Interfaces\Api
{
    /**
     * Return a list of boosts that a user needs to review
     * @param array $pages
     */
    public function get($pages)
    {
        return Factory::response([]);
    }

    /**
     * Boost an entity
     * @param array $pages
     *
     * API:: /v2/boost/:type/:guid
     */
    public function post($pages)
    {
        return Factory::response([]);
    }

    /**
     * @param array $pages
     */
    public function put($pages)
    {
        return Factory::response([]);
    }

    /**
     */
    public function delete($pages)
    {
        return Factory::response([]);
    }
}
