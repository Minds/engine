<?php
/**
 * Minds Logout Endpoint
 *
 * @version 1
 * @author Mark Harding
 */
namespace Minds\Controllers\api\v1;

use Minds\Core;
use Minds\Entities;
use Minds\Interfaces;
use Minds\Api\Factory;

class logout implements Interfaces\Api
{
    public function get($pages)
    {
    }
    
    /**
     * Logout
     * @param $pages
     *
     * @SWG\Post(
     *     summary="Logout",
     *     path="/v1/logout",
     *     @SWG\Response(name="200", description="Array")
     * )
     */
    public function post($pages)
    {
        return Factory::response([]);
    }

    public function put($pages)
    {
    }
    public function delete($pages)
    {
    }
}
