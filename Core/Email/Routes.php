<?php
namespace Minds\Core\Email;

use Minds\Core\Di\Ref;
use Minds\Core\Email\Controllers\EmailAddressController;
use Minds\Core\Router\ModuleRoutes;
use Minds\Core\Router\Route;
use Minds\Core\Router\Middleware\LoggedInMiddleware;

/**
 * Email Routes.
 */
class Routes extends ModuleRoutes
{
    /**
     * Register routes.
     * @inheritDoc
     * @return void
     */
    public function register(): void
    {
        $this->route
            ->withPrefix('api/v3/email')
            ->do(function (Route $route) {
                $route
                    ->withMiddleware([
                        LoggedInMiddleware::class,
                    ])
                    ->do(function (Route $route) {
                        $route->get(
                            'address',
                            Ref::_(EmailAddressController::class, 'getEmailAddress')
                        );
                        $route->post(
                            'confirm',
                            Ref::_('Email\Confirmation\Controller', 'confirmEmail')
                        );
                        $route->post(
                            'send',
                            Ref::_('Email\Confirmation\Controller', 'sendEmail')
                        );
                        $route->post(
                            'verify',
                            Ref::_('Email\Confirmation\Controller', 'verifyCode')
                        );
                    });
            });
    }
}
