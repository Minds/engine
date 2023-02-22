<?php

// ojm remove this file?
/**
 * features
 *
 * @author edgebal
 */

namespace Minds\Controllers\api\v2\admin;

use Exception;
use Minds\Api\Factory;
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
        return Factory::response([]);
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
