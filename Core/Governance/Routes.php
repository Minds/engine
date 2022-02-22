<?php

namespace Minds\Core\Governance;

use Minds\Core\Di\Ref;
use Minds\Core\Router\Middleware\LoggedInMiddleware;
use Minds\Core\Router\ModuleRoutes;
use Minds\Core\Router\Route;

class Routes extends ModuleRoutes
{
    public function register(): void
    {
        $this->route
            ->withPrefix('api/v3/governance')
            ->do(function (Route $route) {
                $route
                    ->do(function (Route $route) {
                        $route->get(
                            'proposals',
                            Ref::_('Governance\Controller', 'getProposals')
                        );
                        $route->get(
                            'proposal/:id',
                            Ref::_('Governance\Controller', 'getProposalsById')
                        );
                    });
                $route
                    // ->withMiddleware([
                    //     LoggedInMiddleware::class
                    // ])
                    ->do(function (Route $route) {
                        $route->delete(
                            'proposal/:id',
                            Ref::_('Governance\Controller', 'deleteProposal')
                        );
                        $route->post(
                            'insert',
                            Ref::_('Governance\Controller', 'insertProposal')
                        );
                    
                    });

            });
    }
}
