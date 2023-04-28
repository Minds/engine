<?php
/**
 * Minds Boost Api endpoint
 *
 * @version 2
 * @author Mark Harding
 * @deprecated
 */

namespace Minds\Controllers\api\v2;

use Minds\Api\Factory;
use Minds\Interfaces;

class boost implements Interfaces\Api
{
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
     * @return mixed|null
     */
    public function put($pages)
    {
        return Factory::response([]);
    }

    /**
     * Called when a network boost is revoked
     * @param array $pages
     */
    public function delete($pages)
    {
        return Factory::response([]);
    }
}
