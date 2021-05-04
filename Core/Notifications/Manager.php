<?php
namespace Minds\Core\Security\TOTP;

use Minds\Core\Di\Di;
use Minds\Entities\User;
use Minds\Common\Repository\Response;
use Minds\Core\Security\TOTP\Repository;
use Minds\Core\Security\TOTP\TOTPSecret;
use Minds\Core\Security\Password;
use Minds\Exceptions\UserErrorException;
use Exception;
use Minds\Core\EntitiesBuilder;

/**
 * TOTP Manager
 * @package Minds\Core\Security\TOTP
 */
class Manager
{
    /** Future proofing */
    /** @var string */
    public const DEFAULT_DEVICE_ID = 'app';

    /** @var Repository $repository */
    protected $repository;

    /** @var Password */
    protected $password;

    /** @var EntitiesBuilder */
    protected $entitiesBuilder;

    /** @var Delegates\EmailDelegate */
    private $emailDelegate;

    public function __construct(
        Repository $repository = null,
        Password $password = null,
        EntitiesBuilder $entitiesBuilder = null,
        Delegates\EmailDelegate $emailDelegate = null
    ) {
        $this->repository = $repository ?? new Repository();
        $this->password = $password ?? Di::_()->get('Security\Password');
        $this->entitiesBuilder = $entitiesBuilder ?? Di::_()->get('EntitiesBuilder');
        $this->emailDelegate = $emailDelegate ?: new Delegates\EmailDelegate();
    }

    /**
     * Get the secret associated with this user
     * @param TOTPSecretQueryOpts $opts
     * @return TOTPSecret
     */
    public function get(TOTPSecretQueryOpts $opts): ?TOTPSecret
    {
        if (!$opts->getUserGuid()) {
            throw new Exception("User guid must be provided");
        }

        return $this->repository->get($opts);
    }

    /**
     * Checks if user already has a secret registered to their channel
     * @param User $user
     * @return bool
     */
    public function isRegistered(User $user): bool
    {
        $opts = new TOTPSecretQueryOpts();
        $opts->setUserGuid($user->getGuid());

        $response = $this->repository->get($opts);

        if (!$response) {
            return false;
        }
        return true;
    }

    /**
     * Checks that the recovery code is correct, user credentials match and then resets
     * @param string $username
     * @param string $password
     * @param string $recoveryCode
     * @return bool
     */
    public function recover(string $username, string $password, string $recoveryCode): bool
    {
        /** @var User */
        $user = $this->entitiesBuilder->getByUserByIndex($username);

        if (!$user) {
            throw new UserErrorException("User not found");
        }

        if (!$this->password->check($user, $password)) {
            throw new UserErrorException("Incorrect username password combination");
        }

        $opts = new TOTPSecretQueryOpts();
        $opts->setUserGuid($user->getGuid());

        $totpSecret = $this->repository->get($opts);

        if (!$totpSecret) {
            throw new Exception("User not registered for 2FA");
        }

        $recoveryHash = $totpSecret->getRecoveryHash();

        /**
         * Disable 2FA when the user uses the recovery code
         */
        if (password_verify($recoveryCode, $recoveryHash)) {
            $success = $this->repository->delete($opts);
            if ($success) {
                $this->emailDelegate->onRecover($user);
            }
            return $success;
        }

        return false;
    }

    /**
     * Adds a user's secret
     * @param TOTPSecret $totpSecret
     * @return bool
     */
    public function add(TOTPSecret $totpSecret): bool
    {
        $added = $this->repository->add($totpSecret);

        return $added;
    }

    /**
     * Deletes a user's secret
     * @param TOTPSecretQueryOpts $opts
     * @return bool
     */
    public function delete(TOTPSecretQueryOpts $opts): bool
    {
        $deleted = $this->repository->delete($opts);

        return $deleted;
    }
}
