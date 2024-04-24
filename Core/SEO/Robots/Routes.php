<?php
declare(strict_types=1);

namespace Minds\Core\SEO\Robots;

use Minds\Core\Di\Ref;
use Minds\Core\Router\ModuleRoutes;
use Minds\Core\Router\Route;
use Minds\Core\SEO\Robots\Controllers\RobotsFileController;

/**
 * SEO Robots Routes
 * @package Minds\Core\SEO\Robots
 */
class Routes extends ModuleRoutes
{
    /**
     * @inheritDoc
     */
    public function register(): void
    {
        $this->route
            ->withPrefix('/')
            ->do(function (Route $route) {
                $route->get(
                    'robots.txt',
                    Ref::_(RobotsFileController::class, 'getRobotsSeoFile')
                );
            });
    }
}
