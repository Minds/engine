<?php

namespace Minds\Controllers\api\v2\permissions;

use Minds\Api\Factory;
use Minds\Interfaces;
use Minds\Core\Di\Di;

class roles implements Interfaces\Api
{
    public function get($pages)
    {
        Factory::isLoggedIn();
        if (!isset($pages[0])) {
            return Factory::response([
                'status' => 'error',
                'message' => 'User guid must be provided',
            ]);
        }

        try {
            /** @var Core\Permissions\Manager $manager */
            $manager = Di::_()->get('Permissions\Manager');
            $opts = [
                'user_guid' => $pages[0],
                'guids' => $_GET['guids'],
            ];
            $permissions = $manager->getList($opts);

            return Factory::response([
                'status' => 'success',
                'roles' => $permissions,
            ]);
        } catch (\Exception $ex) {
            return Factory::response([
                'status' => 'error',
                'message' => $ex->getMessage(),
            ]);
        }

        return Factory::response([]);
    }

    public function post($pages)
    {
        // TODO: Implement put() method.
    }

    public function put($pages)
    {
        // TODO: Implement put() method.
    }

    public function delete($pages)
    {
        // TODO: Implement put() method.
    }
}
