<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\MobileConfigs;

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
            ->withPrefix('api/v3/multi-tenant/mobile-configs')
            ->do(function (Route $route) {
                // logged-out routes.
                $route->get(
                    'image/:imageType',
                    Ref::_(Controllers\MobileConfigPsrController::class, 'get')
                );

                $route->post(
                    'update-preview',
                    Ref::_(Controllers\MobileConfigPreviewPsrController::class, 'processMobilePreviewWebhook')
                );

                $route->get(
                    'qr-code',
                    Ref::_(Controllers\MobilePreviewQRCodeController::class, 'getQrCode')
                );

                $route->get(
                    'qr-code-link',
                    Ref::_(Controllers\MobilePreviewQRCodeController::class, 'redirectToMobilePreviewDeepLink')
                );

                // admin routes.
                $route
                    ->withMiddleware([
                        AdminMiddleware::class,
                    ])
                    ->do(function (Route $route): void {
                        $route->post(
                            'image/upload',
                            Ref::_(Controllers\MobileConfigPsrController::class, 'upload')
                        );
                    });
            });
    }
}
