<?php

namespace Minds\Controllers\api\v1;

use Minds\Api\Factory;

use Minds\Interfaces;

class info implements Interfaces\Api
{
    public function get($pages)
    {
        phpinfo();
    }

    /**
     * Send a wire to someone.
     *
     * @param array $pages
     *
     * API:: /v1/wire/:guid
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
