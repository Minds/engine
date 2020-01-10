<?php
namespace Minds\Core\Sessions\Delegates;

use Sentry;
use Minds\Core\Sessions\Session;

class SentryScopeDelegate
{
    /**
     * Pass through a user guid to sentry
     * @param Session $session
     * @return void
     */
    public function onSession(Session $session): void
    {
        Sentry\configureScope(function (Sentry\State\Scope $scope) use ($session): void {
            $scope->setUser([
                'id' => (string) $session->getUserGuid(),
            ]);
        });
    }
}
