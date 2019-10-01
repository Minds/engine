<?php

namespace Minds\Controllers\api\v2\permissions;

use Minds\Api\Factory;
use Minds\Core\Di\Di;
use Minds\Interfaces;
use Minds\Core\Entities\Actions\Save;
use Minds\Core\Session;
use Minds\Core\Permissions\Roles\Roles;
use Minds\Core\Permissions\Roles\Flags;

class schema implements Interfaces\Api
{
    public function get($pages)
    {
        $response = [
           'roles' => Roles::toArray(),
           'flags' => Flags::toArray()
        ];
        return Factory::response($response);
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
