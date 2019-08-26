<?php
/**
 * Minds: Monetize
 *
 * @version 1
 * @author Mark Harding
 *
 */
namespace Minds\Controllers\api\v1;

use Minds\Core;
use Minds\Helpers;
use Minds\Entities;
use Minds\Interfaces;
use Minds\Api\Factory;

class monetize implements Interfaces\Api
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
        if (!Core\Session::getLoggedinUser()->monetized && !Core\Session::isAdmin()) {
            return Factory::response([
              'status' => 'error',
              'message' => "You don't have permission"
            ]);
        }

        $entity = Entities\Factory::build($pages[0]);

        if (!$entity) {
            return Factory::response([
              'status' => 'error',
              'message' => "Entity not found"
            ]);
        }

        $entity->monetized = true;
        $entity->save();

        $db = new Core\Data\Call('entities_by_time');
        $e = new Core\Data\Call('entities');
        foreach ($db->getRow("activity:entitylink:$entity->guid") as $guid => $ts) {
            $e->insert($guid, ['monetized' => true]);
        }

        (new Core\Payments\Plans\PaywallReview())
            ->setEntityGuid($entity->guid)
            ->add();

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

        $db = new Core\Data\Call('entities_by_time');
        $e = new Core\Data\Call('entities');
        foreach ($db->getRow("activity:entitylink:$entity->guid") as $guid => $ts) {
            $e->insert($guid, ['monetized' => false]);
        }

        (new Core\Payments\Plans\PaywallReview())
            ->setEntityGuid($entity->guid)
            ->remove();

        return Factory::response([]);
    }
}
