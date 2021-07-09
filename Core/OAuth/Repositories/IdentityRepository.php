<?php
namespace Minds\Core\OAuth\Repositories;

use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;
use Minds\Core\OAuth\Entities\UserEntity;
use Minds\Entities\User;
use Minds\Exceptions\UserErrorException;
use OpenIDConnectServer\Repositories\IdentityProviderInterface;

class IdentityRepository implements IdentityProviderInterface
{
    /** @var EntitiesBuilder */
    protected $entitiesBuilder;

    public function __construct(EntitiesBuilder $entitiesBuilder = null)
    {
        $this->entitiesBuilder = $entitiesBuilder ?? Di::_()->get('EntitiesBuilder');
    }

    /**
     * @inheritDoc
     */
    public function getUserEntityByIdentifier($identifier): UserEntity
    {
        $user = $this->entitiesBuilder->single($identifier);

        if (!$user || !$user instanceof User) {
            throw new UserErrorException('User not found');
        }

        $userEntity =  new UserEntity();
        $userEntity->setIdentifier($identifier);
        $userEntity->setUser($user);

        return $userEntity;
    }
}
