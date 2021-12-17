<?php
/**
 * Minds OAuth UserRepository
 */
namespace Minds\Core\OAuth\Repositories;

use Composer\Semver;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Repositories\UserRepositoryInterface;
use Minds\Common\PseudonymousIdentifier;
use Minds\Core\OAuth\Entities\UserEntity;
use Minds\Core\Security\Password;
use Minds\Core\Security\TwoFactor;
use Minds\Entities\User;
use Minds\Core\Di\Di;
use Zend\Diactoros\ServerRequestFactory;

class UserRepository implements UserRepositoryInterface
{
    /** @var Password */
    private $password;

    /** @var Delegates\SentryScopeDelegate */
    private $sentryScopeDelegate;

    /** @var TwoFactor\Manager */
    private $twoFactorManager;

    /** @var User */
    public $mockUser = false;

    public function __construct(
        Password $password = null,
        Delegates\SentryScopeDelegate $sentryScopeDelegate = null,
        $twoFactorManager = null,
        protected ?PseudonymousIdentifier $pseudonymousIdentifier = null
    ) {
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

        /**
         * If the user is banned or in a limited state
         */
        if ($user->isBanned() || !$user->isEnabled()) {
            return false;
        }

        $this->twoFactorManager->gatekeeper($user, ServerRequestFactory::fromGlobals(), enableEmail: false);

        $entity = new UserEntity();
        $entity->setIdentifier($user->getGuid());

        // Update Sentry scope with our user
        $this->sentryScopeDelegate->onGetUserEntity($entity);

        // Instantiate our pseudonymous identifier for analytics
        $this->pseudonymousIdentifier
            ?->setUser($user)
            ->generateWithPassword($password);

        return $entity;
    }
}
