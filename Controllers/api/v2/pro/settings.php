<?php
/**
 * settings
 * @author edgebal
 */

namespace Minds\Controllers\api\v2\pro;

use Exception;
use Minds\Core\Di\Di;
use Minds\Core\Pro\Manager;
use Minds\Core\Session;
use Minds\Entities\User;
use Minds\Interfaces;
use Minds\Api\Factory;
use Minds\Core\EntitiesBuilder;

class settings implements Interfaces\Api
{
    /**
     * Equivalent to HTTP GET method
     * @param array $pages
     * @return mixed|null
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

            $user = Di::_()->get(EntitiesBuilder::class)->getByUserByIndex(strtolower($pages[0]));
        }

        /** @var Manager $manager */
        $manager = Di::_()->get('Pro\Manager');
        $manager
            ->setUser($user)
            ->setActor(Session::getLoggedinUser());

        return Factory::response([
            'isActive' => $manager->isActive(),
            'settings' => $manager->get(),
        ]);
    }

    /**
     * Equivalent to HTTP POST method
     * @param array $pages
     * @return mixed|null
     */
    public function post($pages)
    {
        $user = Session::getLoggedinUser();

        if (isset($pages[0]) && $pages[0]) {
            if (!Session::isAdmin()) {
                return Factory::response([
                    'status' => 'error',
                    'message' => 'You are not authorized',
                ]);
            }

            $user = Di::_()->get(EntitiesBuilder::class)->getByUserByIndex(strtolower($pages[0]));
        }

        /** @var Manager $manager */
        $manager = Di::_()->get('Pro\Manager');
        $manager
            ->setUser($user)
            ->setActor(Session::getLoggedinUser());

        // if (!$manager->isActive()) {
        //     return Factory::response([
        //         'status' => 'error',
        //         'message' => 'You are not Pro',
        //     ]);
        // }

        try {
            $success = $manager->set($_POST);

            if (!$success) {
                throw new Exception('Cannot save Pro settings');
            }
        } catch (\Exception $e) {
            return Factory::response([
                'status' => 'error',
                'message' => $e->getMessage(),
            ]);
        }

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
