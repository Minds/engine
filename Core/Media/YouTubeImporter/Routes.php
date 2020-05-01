<?php
/**
 * Routes
 * @author edgebal
 */

namespace Minds\Core\Media\YouTubeImporter;

use Minds\Core\Di\Ref;
use Minds\Core\Router\Middleware\LoggedInMiddleware;
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
            ->withPrefix('api/v3/media/youtube-importer')
            ->withMiddleware([
                LoggedInMiddleware::class,
            ])
            ->do(function (Route $route) {
                // Requests OAuth token
                $route->get(
                    'account',
                    Ref::_('Media\YouTubeImporter\Controller', 'getToken')
                );

                // Requests OAuth token
                $route->delete(
                    'account',
                    Ref::_('Media\YouTubeImporter\Controller', 'disconnectAccount')
                );

                // Requests OAuth token
                $route->get(
                    'account/redirect',
                    Ref::_('Media\YouTubeImporter\Controller', 'receiveAccessCode')
                );

                // returns list of videos
                $route->get(
                    'videos',
                    Ref::_('Media\YouTubeImporter\Controller', 'getVideos')
                );

                // returns a count of videos by status
                $route->get(
                    'videos/count',
                    Ref::_('Media\YouTubeImporter\Controller', 'getCount')
                );

                // imports a video
                $route->post(
                    'videos/import',
                    Ref::_('Media\YouTubeImporter\Controller', 'import')
                );
                // cancels a video import
                $route->delete(
                    'videos/import',
                    Ref::_('Media\YouTubeImporter\Controller', 'cancel')
                );

                // Subscribe to a channel
                $route->post(
                    'subscribe',
                    Ref::_('Media\YouTubeImporter\Controller', 'subscribe')
                );
                $route->delete(
                    'subscribe',
                    Ref::_('Media\YouTubeImporter\Controller', 'subscribe')
                );

                // YT webhook
                $route->get(
                    'hook',
                    Ref::_('Media\YouTubeImporter\Controller', 'callback')
                );
            });
    }
}
