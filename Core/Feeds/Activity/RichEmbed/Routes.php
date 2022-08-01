<?php

namespace Minds\Core\Feeds\Activity\RichEmbed;

use Minds\Core\Di\Ref;
use Minds\Core\Router\Middleware\AdminMiddleware;
use Minds\Core\Router\ModuleRoutes;
use Minds\Core\Router\Route;

class Routes extends ModuleRoutes
{
    /**
     * Registers all module routes
     */
    public function register(): void
    {
        $this->route
            ->withPrefix('api/v3/rich-embed')
            ->do(function (Route $route) {
                $route
                    ->withMiddleware([
                        AdminMiddleware::class
                    ])
                    ->do(function (Route $route) {
                        $route->delete(
                            'purge',
                            Ref::_('Metascraper\Controller', 'purge')
                        );
                    });
            });
    }
}
