<?php
namespace Minds\Core\OAuth\Repositories\Delegates;

use Sentry;
use Minds\Core\OAuth\Entities\UserEntity;

class SentryScopeDelegate
{
    /**
     * Pass through a user guid to sentry
     * @param UserEntity $entity
     * @return void
     */
    public function onGetUserEntity(UserEntity $entity): void
    {
        Sentry\configureScope(function (Sentry\State\Scope $scope) use ($entity): void {
            $scope->setUser([
                'id' => (string) $entity->getIdentifier(),
            ]);
        });
    }
}
