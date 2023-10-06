<?php

declare(strict_types=1);

namespace Minds\Core\ActivityPub;

use Minds\Core\Di\Ref;
use Minds\Core\Router\ModuleRoutes;
use Minds\Core\Router\Route;

class Routes extends ModuleRoutes
{
    public function register(): void
    {
        $this->route
            ->withPrefix('api/activitypub')
            ->do(function (Route $route) {
                $route->get(
                    'users/:guid',
                    Ref::_(Controller::class, 'getUser')
                );
                
                $route->post(
                    'users/:guid/inbox',
                    Ref::_(Controller::class, 'postInbox')
                );

                $route->get(
                    'users/:guid/outbox',
                    Ref::_(Controller::class, 'getUserOutbox')
                );

                $route->get(
                    'users/:guid/followers',
                    Ref::_(Controller::class, 'getUserFollowers')
                );

                $route->get(
                    'users/:guid/following',
                    Ref::_(Controller::class, 'getUserFollowing')
                );

                $route->get(
                    'users/:guid/liked',
                    Ref::_(Controller::class, 'getUserLiked')
                );

                $route->get(
                    'users/:guid/entities/:urn',
                    Ref::_(Controller::class, 'getObject')
                );

                $route->get(
                    'users/:guid/entities/:urn/activity',
                    Ref::_(Controller::class, 'getActivity')
                );

                $route->post(
                    'inbox',
                    Ref::_(Controller::class, 'postInbox')
                );

                $route->get(
                    'actor',
                    Ref::_(Controller::class, 'getMindsApplicationActor')
                );
            });
    }
}
