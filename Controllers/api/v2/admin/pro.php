<?php
/**
 * pro
 *
 * @author edgebal
 */

namespace Minds\Controllers\api\v2\admin;

use Minds\Api\Factory;
use Minds\Core\Pro\Manager;
use Minds\Entities\User as UserEntity;
use Minds\Interfaces;
use Minds\Core\Di\Di;

class pro implements Interfaces\Api, Interfaces\ApiAdminPam
{
    /**
     * Equivalent to HTTP GET method
     * @param array $pages
     * @return mixed|null
     */
    public function get($pages)
    {
        return Factory::response([]);
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
        if (!($pages[0] ?? null)) {
            return Factory::response([
                'status' => 'error',
                'message' => 'Emtpy target',
            ]);
        }

        $target = new UserEntity($pages[0]);

        if (!$target || !$target->guid) {
            return Factory::response([
                'status' => 'error',
                'message' => 'Invalid target',
            ]);
        }

        /** @var Manager $manager */
        $manager = Di::_()->get('Pro\Manager');
        $manager
            ->setUser($target);

        $success = $manager->enable(time() + (365 * 86400));

        if (!$success) {
            return Factory::response([
                'status' => 'error',
                'message' => 'Error disabling Pro',
            ]);
        }

        return Factory::response([]);
    }

    /**
     * Equivalent to HTTP DELETE method
     * @param array $pages
     * @return mixed|null
     */
    public function delete($pages)
    {
        if (!($pages[0] ?? null)) {
            return Factory::response([
                'status' => 'error',
                'message' => 'Emtpy target',
            ]);
        }

        $target = new UserEntity($pages[0]);

        if (!$target || !$target->guid) {
            return Factory::response([
                'status' => 'error',
                'message' => 'Invalid target',
            ]);
        }

        /** @var Manager $manager */
        $manager = Di::_()->get('Pro\Manager');
        $manager
            ->setUser($target);

        $success = $manager->disable();

        if (!$success) {
            return Factory::response([
                'status' => 'error',
                'message' => 'Error disabling Pro',
            ]);
        }

        return Factory::response([]);
    }
}
