<?php
namespace Minds\Core\Media\Audio;

use Minds\Core\Di\Ref;
use Minds\Core\Media\Audio\AudioPsrController;
use Minds\Core\Router\ModuleRoutes;
use Minds\Core\Router\Route;

/**
 * Audio Routes
 */
class Routes extends ModuleRoutes
{
    /**
     * @inheritDoc
     */
    public function register(): void
    {
        $this->route
            ->withPrefix('fs/v3/media/audio')
            ->do(function (Route $route) {
                $route->get(
                    ':guid/download',
                    Ref::_(AudioPsrController::class, 'downloadAudioAsset')
                );

                $route->get(
                    ':guid/thumbnail',
                    Ref::_(AudioPsrController::class, 'getThumbnail')
                );
            });
    }
}
