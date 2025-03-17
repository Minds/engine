<?php
declare(strict_types=1);

namespace Minds\Core\Media;

use Minds\Core\Di\Ref;
use Minds\Core\Media\Video\VideoController;
use Minds\Core\Router\ModuleRoutes;
use Minds\Core\Router\Route;

class Routes extends ModuleRoutes
{
    public function register(): void
    {
        $this->route
            ->withPrefix('api/v3/media/video')
            ->do(function (Route $route) {
                $route->do(function (Route $route) {
                    $route->get(
                        'download/:guid',
                        Ref::_(VideoController::class, 'download')
                    );
                });
            });
    }
}
