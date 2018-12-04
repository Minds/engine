<?php
/**
 * Minds How many hours
 *
 * @version 2
 * @author Brian Hatchet
 */
namespace Minds\Controllers\api\v2;

use Minds\Core;
use Minds\Core\Di\Di;
use Minds\Interfaces;
use Minds\Api\Factory;
use Minds\Entities;

class hours implements Interfaces\Api
{
    /**
     * Returns the number seconds a user has been logged in as
     * @param  array $pages
     * @return mixed|null
     */
    public function get($pages)
    {
        $response = [];
        $user = Core\Session::getLoggedInUser();
        $response['seconds'] = $user->time_created;
        return Factory::response($response);
    }


    /**
     * Equivalent to HTTP POST method
     * @param  array $pages
     * @return mixed|null
     */
    public function post($pages)
    {
        return Factory::response([]);
    }

    /**
     * Equivalent to HTTP PUT method
     * @param  array $pages
     * @return mixed|null
     */
    public function put($pages)
    {
        return Factory::response([]);
    }

    /**
     * Equivalent to HTTP DELETE method
     * @param  array $pages
     * @return mixed|null
     */
    public function delete($pages)
    {
        return Factory::response([]);
    }
}
