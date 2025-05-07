<?php
declare(strict_types=1);

namespace Minds\Core\Security\ForgotPassword\Services;

use Minds\Core\Di\Di;
use Minds\Core\Email\V2\Campaigns\Recurring\ForgotPassword\ForgotPasswordEmailer;
use Minds\Core\Entities\Actions\Save as SaveAction;
use Minds\Core\Security\ACL;
use Minds\Core\Security\Audit\Services\AuditService;
use Minds\Core\Security\ForgotPassword\Cache\ForgotPasswordCache;
use Minds\Core\Security\Password;
use Minds\Core\Sessions\CommonSessions\Manager as CommonSessionsManager;
use Minds\Core\Sessions\Manager as SessionsManager;
use Minds\Entities\User;
use Minds\Exceptions\UserErrorException;

/**
 * Service for handling user forgot password requests.
 */
class ForgotPasswordService
{
    public function __construct(
        private ?ForgotPasswordCache $cache = null,
        private ?ForgotPasswordEmailer $forgotPasswordEmailer = null,
        private ?CommonSessionsManager $commonSessionsManager = null,
        private ?SessionsManager $sessionsManager = null,
        private ?SaveAction $saveAction = null,
        private ?ACL $acl = null,
        private ?AuditService $auditService = null,
    ) {
        $this->cache ??= Di::_()->get(ForgotPasswordCache::class);
        $this->forgotPasswordEmailer ??= new ForgotPasswordEmailer();
        $this->commonSessionsManager ??= new CommonSessionsManager();
        $this->sessionsManager ??= new SessionsManager();
        $this->saveAction ??= new SaveAction();
        $this->acl ??= Di::_()->get(ACL::class);
        $this->auditService ??= Di::_()->get(AuditService::class);
    }

    /**
     * Request password reset for a user.
     * @param User $user - user to request reset for.
     * @return bool true on success.
     */
    public function request(User $user): bool
    {
        $code = $this->cache->get((int) $user->getGuid()) ?? Password::reset($user);

        $this->cache->set((int) $user->getGuid(), $code);

        $this->forgotPasswordEmailer
            ->setUser($user)
            ->setCode($code)
            ->send();

        return true;
    }

    /**
     * Reset password with provided code.
     * @param User $user - user to reset password for.
     * @param string $code - reset code.
     * @param string $password - new password.
     * @return bool true on success.
     * @throws UserErrorException
     */
    public function reset(User $user, string $code, string $password): bool
    {
        $cachedCode = $this->cache->get((int) $user->getGuid());

        if ($code !== $user->password_reset_code || $cachedCode !== $code) {
            throw new UserErrorException("Invalid reset code");
        }

        $user->password = Password::generate($user, $password);
        $user->password_reset_code = "";
        $user->override_password = true;

        $ia = $this->acl->setIgnore(true);

        $this->saveAction
            ->setEntity($user)
            ->withMutatedAttributes([
                'password',
                'password_reset_code'
            ])
            ->save();

        $this->acl->setIgnore($ia);

        $this->cache->delete((int) $user->getGuid());

        $this->createNewSession($user);

        $this->auditService->log(
            event: 'password_reset',
            properties: [],
            user: $user,
        );

        return true;
    }

    /**
     * Create a new session for the user, and removes all existing sessions.
     * @param User $user - user to create session for.
     * @return void
     */
    private function createNewSession(User $user): void
    {
        $this->commonSessionsManager->deleteAll($user);
        $this->sessionsManager->setUser($user);
        $this->sessionsManager->createSession()
            ->save();

        set_last_login($user);
    }
}
