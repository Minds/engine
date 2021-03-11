<?php
/**
 * Minds OAuth UserRepository
 */
namespace Minds\Core\OAuth\Repositories;

use Composer\Semver;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Repositories\UserRepositoryInterface;
use Minds\Core\OAuth\Entities\UserEntity;
use Minds\Core\Security\Password;
use Minds\Core\Security\TwoFactor;
use Minds\Entities\User;
use Minds\Core\Di\Di;
use Zend\Diactoros\ServerRequestFactory;

class UserRepository implements UserRepositoryInterface
{
    /** @var Password $password */
    private $password;

    /** @var SentryScopeDelegate $sentryScopeDelegate */
    private $sentryScopeDelegate;

    /** @var TwoFactor\Manager */
    private $twoFactorManager;

    /** @var User $mock */
    public $mockUser = false;

    public function __construct(Password $password = null, Delegates\SentryScopeDelegate $sentryScopeDelegate = null, $twoFactorManager = null)
    {
        $this->password = $password ?: Di::_()->get('Security\Password');
        $this->sentryScopeDelegate = $sentryScopeDelegate ?? new Delegates\SentryScopeDelegate;
        $this->twoFactorManager = $twoFactorManager ?? Di::_()->get('Security\TwoFactor\Manager');
    }

    /**
     * {@inheritdoc}
     */
    public function getUserEntityByUserCredentials(
        $username,
        $password,
        $grantType,
        ClientEntityInterface $clientEntity
    ) {
        if (!$username || !$password) {
            return false;
        }

        if ($this->mockUser) {
            $user = $this->mockUser;
        } else {
            $user = new User(strtolower($username));
        }

        if (!$user->getGuid()) {
            return false;
        }

        if (!$this->password->check($user, $password)) {
            return false;
        }

        if (Semver\Comparator::lessThan($_SERVER['HTTP_APP_VERSION'], '4.10.0')) {
            // TODO: Remove the semver comparitor once 4.10 mobile build is widely used
        } else {
            $this->twoFactorManager->gatekeeper($user, ServerRequestFactory::fromGlobals());
        }

        $entity = new UserEntity();
        $entity->setIdentifier($user->getGuid());

        // Update Sentry scope with our user
        $this->sentryScopeDelegate->onGetUserEntity($entity);

        return $entity;
    }
}
