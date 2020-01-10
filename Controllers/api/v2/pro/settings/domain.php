<?php
/**
 * domain
 * @author edgebal
 */

namespace Minds\Controllers\api\v2\pro\settings;

use Exception;
use Minds\Core\Di\Di;
use Minds\Core\Pro\Domain as ProDomain;
use Minds\Core\Session;
use Minds\Entities\User;
use Minds\Interfaces;
use Minds\Api\Factory;

class domain implements Interfaces\Api
{
    /**
     * Equivalent to HTTP GET method
     * @param array $pages
     * @return mixed|null
     * @throws Exception
     */
    public function get($pages)
    {
        $user = Session::getLoggedinUser();

        if (isset($pages[0]) && $pages[0]) {
            if (!Session::isAdmin()) {
                return Factory::response([
                    'status' => 'error',
                    'message' => 'You are not authorized',
                ]);
            }

            $user = new User(strtolower($pages[0]));
        }

        /** @var ProDomain $proDomain */
        $proDomain = Di::_()->get('Pro\Domain');

        return Factory::response([
            'isValid' => $proDomain->isAvailable($_GET['domain'], (string) $user->guid)
        ]);
    }

    /**
     * Equivalent to HTTP POST method
     * @param array $pages
     * @return mixed|null
     */
    public function post($pages)
    {
        return Factory::response([]);
    }

    /**
     * Equivalent to HTTP PUT method
     * @param array $pages
     * @return mixed|null
     */
    public function put($pages)
    {
        return Factory::response([]);
    }

    /**
     * Equivalent to HTTP DELETE method
     * @param array $pages
     * @return mixed|null
     */
    public function delete($pages)
    {
        return Factory::response([]);
    }
}
