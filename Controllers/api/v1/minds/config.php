<?php
/**
 * Minds Settings
 *
 * @author emi
 */

namespace Minds\Controllers\api\v1\minds;

use Minds\Core;
use Minds\Interfaces;
use Minds\Api\Factory;
use Minds\Common\Cookie;

class config implements Interfaces\Api, Interfaces\ApiIgnorePam
{
    /**
     * Equivalent to HTTP GET method
     * @param  array $pages
     * @return mixed|null
     */
    public function get($pages)
    {

        $cookie = new Cookie();
        $cookie
            ->setName('testing')
            ->setValue('true')
            ->setExpire(time() + (60 * 60 * 24 * 30 * 12))
            ->setPath('/')
            ->create();

        return Factory::response(
            Core\Di\Di::_()->get('Config\Exported')
                ->export()
        );
    }

    /**
     * Equivalent to HTTP POST method
     * @param  array $pages
     * @return mixed|null
     */
    public function post($pages)
    {
        return Factory::response([]);
    }

    /**
     * Equivalent to HTTP PUT method
     * @param  array $pages
     * @return mixed|null
     */
    public function put($pages)
    {
        return Factory::response([]);
    }

    /**
     * Equivalent to HTTP DELETE method
     * @param  array $pages
     * @return mixed|null
     */
    public function delete($pages)
    {
        return Factory::response([]);
    }
}
