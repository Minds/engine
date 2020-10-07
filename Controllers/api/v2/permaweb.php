<?php

namespace Minds\Controllers\api\v2;

use Minds\Api\Factory;
use Minds\Core\Di\Di;
use Minds\Core\Session;
use Minds\Interfaces;
use Minds\Core\Permaweb\Manager;

class permaweb implements Interfaces\Api
{
    /** @var array */
    private $options = [
        'headers' => ['Content-Type: application/json']
    ];

    /**
     * Equivalent to HTTP GET method
     * @param array $pages
     * @return mixed|null
     * @throws \Exception
     */
    public function get($pages)
    {
        if (!$pages[0]) {
            return Factory::response([
                "status" => 400,
                "data" => 'You must provide an Arweave address.'
            ]);
        }

        $manager = Di::_()->get('Permaweb\Manager');
        $response = $manager->getById($pages[0]);

        return Factory::response($response);
    }

    /**
     * Equivalent to HTTP POST method
     * @param array $pages
     * @return mixed|null
     * @throws \Exception
     */
    public function post($pages)
    {
        $data = $_POST['user'];
        $guid = Session::getLoggedInUserGuid();

        if (!$data || !$guid) {
            return Factory::response([
                "status" => 400,
                "data" => 'You must be logged in and pass data parameter.'
            ]);
        }

        $manager = Di::_()->get('Permaweb\Manager');
        $response = $manager->save($data, $guid);

        return Factory::response($response);
    }

    /**
     * Equivalent to HTTP PUT method
     * @param array $pages
     * @return mixed|null
     */
    public function put($pages)
    {
        return Factory::response([]);
    }

    /**
     * Equivalent to HTTP DELETE method
     * @param array $pages
     * @return mixed|null
     */
    public function delete($pages)
    {
        return Factory::response([]);
    }
}
