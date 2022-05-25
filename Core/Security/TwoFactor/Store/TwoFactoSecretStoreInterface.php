<?php

namespace Minds\Core\Security\TwoFactor\Store;

use Minds\Entities\User;

/**
 * Store for MFA secrets.
 * Will store values for 15 minutes if a user is trusted, else we can
 * deduce the secret is being used for email confirmation, in which case the TTL
 * is set to 1 day. The key generated for untrusted users also contains no random bytes
 * so that if a user exits their session and comes back, they don't have to immediately
 * generate a new code / email.
 */
interface TwoFactoSecretStoreInterface
{
    /**
     * Get secret from store.
     * @param User $user - user for the entry we want to try to retrieve.
     * @return ?TwoFactorSecret - object containing secret.
     */
    public function get(User $user): ?TwoFactorSecret;

    /**
     * Gets entry by key.
     * @param string $key - key to get entry by.
     * @return ?TwoFactorSecret - object containing secret.
     */
    public function getByKey(string $key): ?TwoFactorSecret;

    /**
     * Set secret in store.
     * @param User $user - user we are setting entry for.
     * @param string $secret - secret we are setting.
     * @return string key - returns the key used to store.
     */
    public function set(User $user, string $secret): string;

    /**
     * Delete an entry by key.
     * @param string $key - key to delete by.
     * @return TwoFactoSecretStoreInterface
     */
    public function delete(string $key): self;

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
    public function getKey(User $user): string;

    /**
     * Gets TTL for store. If not trusted, we are doing email confirmation, thus the ttl is 1 day.
     * For all other actions it is 15 minutes.
     * @param User $user - user to get TTL for.
     * @return int - seconds for TTL.
     */
    public function getTTL(User $user): int;
}
