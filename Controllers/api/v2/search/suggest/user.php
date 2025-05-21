<?php
/**
 * Minds Core Search API
 *
 * @version 2
 * @author Theodore R. Smith <theodore@phpexperts.pro>
 */

namespace Minds\Controllers\api\v2\search\suggest;

use Minds\Core;
use Minds\Core\Di\Di;
use Minds\Interfaces;
use Minds\Api\Factory;
use Minds\Entities;

class user implements Interfaces\Api, Interfaces\ApiIgnorePam
{
    /**
     * Equivalent to HTTP GET method
     * @param  array $pages
     * @return mixed|null
     */
    public function get($pages)
    {
        $usernames = Entities\Repositories\UserRepository::getUsersList();

        // Filter by the ones that match.
        if (!empty($_GET['username'])) {
            $userSearch = $_GET['username'];
            $usernames = array_filter($usernames, function($username) use ($userSearch) {
                return strpos($username, $userSearch) === 0;
            });
        }

        return Factory::response([
            'entities' => $usernames
        ]);

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
