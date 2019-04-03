<?php

namespace Minds\Controllers\api\v2\hashtags;

use Minds\Api\Factory;
use Minds\Core;
use Minds\Core\Di\Di;
use Minds\Interfaces;

class suggested implements Interfaces\Api
{
    /**
     * Gets a list of suggested hashtags, including the ones the user has opted in
     */
    public function get($pages)
    {
        Factory::isLoggedIn();

        $limit = 10;
        if (isset($_GET['limit'])) {
            $limit = intval($_GET['limit']);
        }

        $trending = (bool) ($_GET['trending'] ?? false);
        $defaults = (bool) ($_GET['defaults'] ?? true); // Legacy behavior

        if (!$trending && !$defaults) {
            $defaults = true;
        }

        /** @var Core\Hashtags\User\Manager $manager */
        $manager = Di::_()->get('Hashtags\User\Manager');
        $manager->setUser(Core\Session::getLoggedInUser());

        $result = $manager->get([
            'limit' => $limit,
            'trending' => $trending,
            'defaults' => $defaults,
        ]);

        return Factory::response([
            'status' => 'success',
            'tags' => $result
        ]);
    }

    public function post($pages)
    {
        return Factory::response([]);
    }

    public function put($pages)
    {
        return Factory::response([]);
    }

    public function delete($pages)
    {
        return Factory::response([]);
    }
}
