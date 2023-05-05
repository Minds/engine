<?php
/**
 * Minds Boost Api endpoint
 *
 * @version 1
 * @author Mark Harding
 * @deprecated
 */

namespace Minds\Controllers\api\v1\boost;

use Minds\Api\Factory;
use Minds\Interfaces;

class fetch implements Interfaces\Api
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
     */
    public function post($pages)
    {
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
    }
}
