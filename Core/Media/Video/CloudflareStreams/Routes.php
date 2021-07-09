<?php
/**
 * Routes
 * @author Mark Harding
 */

namespace Minds\Core\Media\Video\CloudflareStreams;

use Minds\Core\Di\Ref;
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
            ->withPrefix('api/v3/media/cloudflare')
            ->do(function (Route $route) {
                $route->get(
                    'sources/:guid',
                    Ref::_('Media\Video\CloudflareStreams\Controllers', 'sources')
                );
                $route->post(
                    'webhooks',
                    Ref::_('Media\Video\CloudflareStreams\Webhooks', 'onWebhook')
                );
            });
    }
}
