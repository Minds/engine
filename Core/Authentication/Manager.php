<?php

declare(strict_types=1);

namespace Minds\Core\Authentication;

use Exception;
use Minds\Common\IpAddress;
use Minds\Common\PseudonymousIdentifier;
use Minds\Common\Repository\Response;
use Minds\Core\Analytics\Metrics\Event;
use Minds\Core\Authentication\Exceptions\AuthenticationAttemptsExceededException;
use Minds\Core\Di\Di;
use Minds\Core\Entities\Actions\Save;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Features\Canary;
use Minds\Core\Router\Exceptions\UnauthorizedException;
use Minds\Core\Security\Exceptions\PasswordRequiresHashUpgradeException;
use Minds\Core\Security\Exceptions\UserNotSetupException;
use Minds\Core\Security\LoginAttempts;
use Minds\Core\Security\Password as PasswordSecurityService;
use Minds\Core\Security\RateLimits\KeyValueLimiter;
use Minds\Core\Security\TwoFactor\Manager as TwoFactorManager;
use Minds\Core\Security\TwoFactor\TwoFactorInvalidCodeException;
use Minds\Core\Security\TwoFactor\TwoFactorRequiredException;
use Minds\Core\Security\XSRF;
use Minds\Core\Session;
use Minds\Core\Sessions\Manager as SessionsManager;
use Minds\Entities\User;
use Minds\Exceptions\NotFoundException;
use RedisException;
use Zend\Diactoros\ServerRequest;

class Manager
{
    public function __construct(
        private ?KeyValueLimiter $keyValueLimiter = null,
        private ?LoginAttempts $loginAttempts = null,
        private ?EntitiesBuilder $entitiesBuilder = null,
        private ?Canary $canary = null,
        private ?SessionsManager $sessionsManager = null,
        private ?TwoFactorManager $twoFactorManager = null,
        private ?Save $save = null,
    ) {
        $this->keyValueLimiter ??= Di::_()->get('Security\RateLimits\KeyValueLimiter');
        $this->loginAttempts ??= Di::_()->get('Security\LoginAttempts');
        $this->entitiesBuilder ??= Di::_()->get('EntitiesBuilder');
        $this->canary ??= Di::_()->get('Features\Canary');
        $this->sessionsManager ??= Di::_()->get('Sessions\Manager');
        $this->twoFactorManager ??= Di::_()->get('Security\TwoFactor\Manager');
        $this->save ??= new Save();
    }

    /**
     * @param string $username
     * @param string $password
     * @param ServerRequest $request
     * @return Response
     * @throws AuthenticationAttemptsExceededException
     * @throws NotFoundException
     * @throws TwoFactorInvalidCodeException
     * @throws TwoFactorRequiredException
     * @throws UnauthorizedException
     * @throws UserNotSetupException
     * @throws RedisException
     */
    public function authenticate(string $username, string $password, ServerRequest $request): Response
    {
        $this->keyValueLimiter
            ->setKey('router-post-api-v3-authenticate')
            ->setValue((new IpAddress())->get())
            ->setSeconds(3600)
            ->setMax(100)
            ->checkAndIncrement();

        $user = $this->entitiesBuilder->single($username);

        if (!($user instanceof User)) {
            throw new NotFoundException("We could not find the provided user");
        }

        $this->loginAttempts->setUser($user);

        if ($this->loginAttempts->checkFailures()) {
            throw new AuthenticationAttemptsExceededException();
        }

        if (!$user->isEnabled() && !$user->isBanned()) {
            $user->enabled = 'yes';

            $this->save
                ->setEntity($user)
                ->withMutatedAttributes(['enabled'])
                ->save();
        }

        try {
            if (!$this->getPasswordSecurityService()->check($user, $password)) {
                $this->loginAttempts->logFailure();
                throw new UnauthorizedException();
            }
        } catch (PasswordRequiresHashUpgradeException $e) {
            $user->password = PasswordSecurityService::generate($user, $password);
            $user->override_password = true;
           
            $this->save
                ->setEntity($user)
                ->withMutatedAttributes(['password'])
                ->save();
        }

        $this->loginAttempts->resetFailuresCount();

        $this->twoFactorManager->gatekeeper($user, $request, enableEmail: false);

        $this->sessionsManager
            ->setUser($user)
            ->createSession()
            ->save();

        set_last_login($user);

        XSRF::setCookie(true);

        $this->canary
            ->setCookie($user->isCanary());

        (new PseudonymousIdentifier())
            ->setUser($user)
            ->generateWithPassword($password);

        $event = new Event();
        $event->setUserGuid($user->getGuid())
            ->setType('action')
            ->setAction('login')
            ->push();

        return new Response([$user]);
    }

    private function getPasswordSecurityService(): PasswordSecurityService
    {
        return new PasswordSecurityService();
    }

    /**
     * @return void
     * @throws Exception
     */
    public function deleteSession(): void
    {
        $this->sessionsManager->delete();
    }

    /**
     * @return void
     */
    public function deleteAllUserSessions(): void
    {
        $this->sessionsManager->deleteAll();
    }
}
