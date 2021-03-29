<?php
/**
 * Minds OAuth user
 */
namespace Minds\Core\OAuth\Entities;

use Minds\Entities\User;
use League\OAuth2\Server\Entities\UserEntityInterface;
use League\OAuth2\Server\Entities\Traits\EntityTrait;
use OpenIDConnectServer\Entities\ClaimSetInterface;

class UserEntity implements UserEntityInterface, ClaimSetInterface
{
    use EntityTrait;

    /** @var User */
    protected $user;

    /**
     * Sets the Minds user
     * @param User $user
     * @return void
     */
    public function setUser($user): void
    {
        $this->user = $user;
    }

    /**
     * @inheritDoc
     */
    public function getClaims(): array
    {
        return [
            'name' => $this->user->getName(),
            'username' => $this->user->getUsername(),
        ];
    }
}
