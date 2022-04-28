<?php
namespace Minds\Core\Captcha\FriendlyCaptcha;

use Minds\Core\Captcha\FriendlyCaptcha\Cache\AttemptsCache;
use Minds\Core\Captcha\FriendlyCaptcha\Cache\PuzzleCache;
use Minds\Core\Di;

/**
 * FriendlyCaptcha provider.
 */
class Provider extends Di\Provider
{
    public function register()
    {
        $this->di->bind('FriendlyCaptcha\Manager', function ($di) {
            return new Manager();
        });
        $this->di->bind('FriendlyCaptcha\Controller', function ($di) {
            return new Controller();
        });
        $this->di->bind('FriendlyCaptcha\AttemptsCache', function ($di) {
            return new AttemptsCache();
        });
        $this->di->bind('FriendlyCaptcha\PuzzleCache', function ($di) {
            return new PuzzleCache();
        });
    }
}
