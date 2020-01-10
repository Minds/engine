<?php
/**
 * Minds Admin: Monetize
 *
 * @version 1
 * @author Mark Harding
 *
 */
namespace Minds\Controllers\api\v1\admin;

use Minds\Core;
use Minds\Helpers;
use Minds\Entities;
use Minds\Interfaces;
use Minds\Api\Factory;

class monetize implements Interfaces\Api, Interfaces\ApiAdminPam
{
    /**
     *
     */
    public function get($pages)
    {
        $response = [];
        return Factory::response($response);
    }

    /**
     * @param array $pages
     */
    public function post($pages)
    {
        return Factory::response([]);
    }

    /**
     * Monetize a post
     * @param array $pages
     */
    public function put($pages)
    {
        $entity = Entities\Factory::build($pages[0]);

        if (!$entity) {
            return Factory::response([
              'status' => 'error',
              'message' => "Entity not found"
            ]);
        }

        $entity->monetized = true;
        $entity->save();

        return Factory::response([]);
    }

    /**
     * @param array $pages
     */
    public function delete($pages)
    {
        $entity = Entities\Factory::build($pages[0]);

        if (!$entity) {
            return Factory::response([
              'status' => 'error',
              'message' => "Entity not found"
            ]);
        }

        $entity->monetized = false;
        $entity->save();

        return Factory::response([]);
    }
}
