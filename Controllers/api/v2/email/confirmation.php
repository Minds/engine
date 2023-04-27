<?php
/**
 * confirmation
 *
 * @author edgebal
 */

namespace Minds\Controllers\api\v2\email;

use Minds\Api\Factory;
use Minds\Core\Di\Di;
use Minds\Core\Email\Confirmation\Manager;
use Minds\Core\Session;
use Minds\Entities\User;
use Minds\Exceptions\DeprecatedException;
use Minds\Interfaces;

class confirmation implements Interfaces\Api
{
    /**
     * GET method
     */
    public function get($pages)
    {
        return Factory::response([]);
    }

    /**
     * POST method
     */
    public function post($pages)
    {
        throw new DeprecatedException();
    }

    /**
     * PUT method
     */
    public function put($pages)
    {
        return Factory::response([]);
    }

    /**
     * DELETE method
     */
    public function delete($pages)
    {
        return Factory::response([]);
    }
}
