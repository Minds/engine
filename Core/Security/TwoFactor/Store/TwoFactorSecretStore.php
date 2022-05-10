<?php

namespace Minds\Core\Security\TwoFactor\Store;

use Minds\Core\Data\cache\PsrWrapper;
use Minds\Core\Di\Di;
use Minds\Core\Security\TwoFactor\Store\TwoFactorSecret;
use Minds\Entities\User;

/**
 * Store for MFA secrets.
 * Will store values for 15 minutes if a user is trusted, else we can
 * deduce the secret is being used for email confirmation, in which case the TTL
 * is set to 1 day. The key generated for untrusted users also contains no random bytes
 * so that if a user exits their session and comes back, they don't have to immediately
 * generate a new code / email.
 */
class TwoFactorSecretStore
{
    /**
     * Constructor.
     * @param ?PsrWrapper $cache - PsrWrapper around cache.
     */
    public function __construct(
        private ?PsrWrapper $cache = null,
    ) {
        $this->cache ??= Di::_()->get('Cache\PsrWrapper');
    }

    /**
     * Get secret from store.
     * @param User $user - user for the entry we want to try to retrieve.
     * @return ?TwoFactorSecret - object containing secret.
     */
    public function get(User $user): ?TwoFactorSecret
    {
        return $this->getByKey($this->getKey($user));
    }

    /**
     * Gets entry by key.
     * @param string $key - key to get entry by.
     * @return ?TwoFactorSecret - object containing secret.
     */
    public function getByKey(string $key): ?TwoFactorSecret
    {
        $storedJson = $this->cache->get($key);
        
        try {
            $storedObject = json_decode($storedJson, true);
        } catch (\Exception $e) {
            return null;
        }

        if (!$storedJson) {
            return null;
        }

        return (new TwoFactorSecret())
            ->setGuid($storedObject['_guid'])
            ->setTimestamp($storedObject['ts'])
            ->setSecret($storedObject['secret']);
    }

    /**
     * Set secret in store.
     * @param User $user - user we are setting entry for.
     * @param string $secret - secret we are setting.
     * @return string key - returns the key used to store.
     */
    public function set(User $user, string $secret): string
    {
        $key = $this->getKey($user);

        $storedSecretJson = json_encode(
            (new TwoFactorSecret())
                ->setGuid($user->guid)
                ->setTimestamp(time())
                ->setSecret($secret)
        );

        $this->cache->set(
            $key,
            $storedSecretJson,
            $this->getTtl($user)
        );

        return $key;
    }

    /**
     * Delete an entry by key.
     * @param string $key - key to delete by.
     * @return TwoFactorSecretStore
     */
    public function delete(string $key): self
    {
        $this->cache->delete($key);
        return $this;
    }

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
    public function getTtl(User $user): int
    {
        return $user->isTrusted() ? 900 : 86400;
    }
}
