<?php
namespace Minds\Core\Security\TOTP;

use Minds\Core\Di\Di;
use Minds\Entities\User;
use Minds\Common\Repository\Response;
use Minds\Core\Security\TOTP\Repository;
use Minds\Core\Security\TOTP\TOTPSecret;
use Minds\Exceptions\UserErrorException;
use Exception;

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

    public function __construct(
        Repository $repository = null
    ) {
        $this->repository = $repository ?? new Repository();
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
     * @param TOTPSecretQueryOpts $opts
     * @return bool
     */
    public function isRegistered(TOTPSecretQueryOpts $opts): bool
    {
        if (!$opts->getUserGuid) {
            throw new \Exception('User guid must be provided');
        }

        $response = $this->repository->get($opts);

        if (!$response) {
            return false;
        }
        return true;
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
