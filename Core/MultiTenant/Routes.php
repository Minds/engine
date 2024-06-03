<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant;

use Minds\Core\Di\Ref;
use Minds\Core\MultiTenant\Controllers\TenantPsrController;
use Minds\Core\Router\Enums\ApiScopeEnum;
use Minds\Core\Router\Middleware\AdminMiddleware;
use Minds\Core\Router\Middleware\NotMultiTenantMiddleware;
use Minds\Core\Router\ModuleRoutes;
use Minds\Core\Router\Route;
use Minds\Core\SEO\Robots\Controllers\RobotsFileController;

class Routes extends ModuleRoutes
{
    /**
     * @inheritDoc
     */
    public function register(): void
    {
        $this->route
            ->withPrefix('/api/v3/multi-tenant')
            ->do(function (Route $route) {
                $route
                    ->withMiddleware([
                        AdminMiddleware::class,
                        NotMultiTenantMiddleware::class,
                    ])
                    ->withScope(ApiScopeEnum::TENANT_CREATE_TRIAL)
                    ->post(
                        'start-trial',
                        Ref::_(TenantPsrController::class, 'startTrial')
                    );
            });
    }
}
