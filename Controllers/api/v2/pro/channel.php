<?php
/**
 * channel
 * @author edgebal
 */

namespace Minds\Controllers\api\v2\pro;

use Exception;
use Minds\Core\Di\Di;
use Minds\Core\Pro\Channel\Manager;
use Minds\Entities\User;
use Minds\Interfaces;
use Minds\Api\Factory;

class channel implements Interfaces\Api
{
    /**
     * Equivalent to HTTP GET method
     * @param array $pages
     * @return mixed|null
     * @throws Exception
     */
    public function get($pages)
    {
        /** @var Manager $manager */
        $manager = Di::_()->get('Pro\Channel\Manager');
        $manager->setUser(new User($pages[0]));

        switch ($pages[1] ?? '') {
            case 'content':
                return Factory::response([
                    'content' => $manager->getAllCategoriesContent(),
                ]);
        }
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
