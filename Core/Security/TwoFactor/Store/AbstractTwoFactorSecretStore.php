<?php

namespace Minds\Core\Security\TwoFactor\Store;

use Minds\Entities\User;

/**
 *
 */
abstract class AbstractTwoFactorSecretStore implements TwoFactoSecretStoreInterface
{
    /**
     * @const int
     */
    private const TRUSTED_USER_KEY_TTL = 900;

    /**
     * @const int
     */
    private const UNTRUSTED_USER_KEY_TTL = 86400;

    /**
     * Derives key by sha512 hashing the username, salt.
     * - if trusted, also hashes random bytes.
     * - if NOT trusted, does NOT add random bytes, because this would mean a user has not
     *   confirmed their email, thus the only action they can take is TO confirm their email.
     *   The random bytes would be lost if the user were to leave email confirmation and try
     *   to come back later, leaving them having to generate a new code / key.
     * @param User $user - user to get key for.
     * @return string key.
     */
    public function getKey(User $user): string
    {
        if ($user->isTrusted()) {
            $bytes = openssl_random_pseudo_bytes(128);
            return hash('sha512', $user->username . $user->salt . $bytes);
        }
        return hash('sha512', $user->username . $user->salt);
    }

    /**
     * Gets TTL for store. If not trusted, we are doing email confirmation, thus the ttl is 1 day.
     * For all other actions it is 15 minutes.
     * @param User $user - user to get TTL for.
     * @return int - seconds for TTL.
     */
    public function getTTL(User $user): int
    {
        return $user->isTrusted() ? self::TRUSTED_USER_KEY_TTL : self::UNTRUSTED_USER_KEY_TTL;
    }
}
