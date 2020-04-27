<?php
/**
 * API for returning a captcha
 */
namespace Minds\Controllers\api\v2;

use Minds\Api\Factory;
use Minds\Common\Cookie;
use Minds\Core\Di\Di;
use Minds\Core\Config;
use Minds\Core\Session;
use Minds\Interfaces;

class captcha implements Interfaces\Api
{
    public function get($pages)
    {
        $captchaManager = Di::_()->get('Captcha\Manager');
        $captcha = $captchaManager->build();
        return Factory::response(
            $captcha->export()
        );
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
