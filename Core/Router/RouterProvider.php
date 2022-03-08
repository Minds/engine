<?php
/**
 * RouterProvider
 * @author edgebal
 */

namespace Minds\Core\Router;

use Minds\Core\Di\Provider;
use Minds\Core\Router\Hooks\ShutdownHandlerManager;

class RouterProvider extends Provider
{
    public function register()
    {
        $this->di->bind('Router', function ($di) {
            return new Dispatcher();
        }, ['useFactory' => true]);

        $this->di->bind('Router\Registry', function ($di) {
            return Registry::_();
        }, ['useFactory' => true]);

        $this->di->bind('Router\Hooks\ShutdownHandlerManager', function ($di) {
            return new ShutdownHandlerManager();
        }, ['useFactory' => true]);
    }
}
