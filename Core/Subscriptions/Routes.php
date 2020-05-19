<?php
namespace Minds\Core\Subscriptions;

use Minds\Core\Di\Ref;
use Minds\Core\Router\ModuleRoutes;
use Minds\Core\Router\Route;
use Minds\Exceptions\UserErrorException;

/**
 * Subscriptions Routes
 * @package Minds\Core\Subscriptions
 */
class Routes extends ModuleRoutes
{
    /**
     * @inheritDoc
     */
    public function register(): void
    {
        $this->route
            ->withPrefix('api/v3/subscriptions/graph')
            ->do(function (Route $route) {
                $route->get(
                    ':guid/subscriptions',
                    Ref::_('Subscriptions\Graph\Controller', 'getSubscriptions')
                );

                $route->get(
                    ':guid/subscribers',
                    function () {
                        throw new UserErrorException('Not implemented');
                    }
                );
            });
    }
}
