<?php
/**
 * features
 *
 * @author edgebal
 */

namespace Minds\Controllers\api\v2\admin;

use Exception;
use Minds\Api\Factory;
use Minds\Core\Features\Manager;
use Minds\Core\Session;
use Minds\Entities\User;
use Minds\Interfaces;
use Minds\Core\Di\Di;

class features implements Interfaces\Api, Interfaces\ApiAdminPam
{
    /**
     * @inheritDoc
     */
    public function get($pages)
    {
        $for = null;

        if (isset($_GET['for'])) {
            try {
                $for = new User(strtolower($_GET['for']));

                if (!$for || !$for->guid) {
                    $for = null;
                }
            } catch (Exception $e) {
                $for = null;
            }
        }

        /** @var Manager $manager */
        $manager = Di::_()->get('Features\Manager');
        return Factory::response(
            $manager->breakdown($for)
        );
    }

    /**
     * @inheritDoc
     */
    public function post($pages)
    {
        return Factory::response([]);
    }

    /**
     * @inheritDoc
     */
    public function put($pages)
    {
        return Factory::response([]);
    }

    /**
     * @inheritDoc
     */
    public function delete($pages)
    {
        return Factory::response([]);
    }
}
