<?php

namespace Minds\Core\Security\TwoFactor\Store;

use Exception;
use Minds\Core\Di\Di;
use Minds\Entities\User;
use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;

/**
 * Store for MFA secrets.
 * Will store values for 15 minutes if a user is trusted, else we can
 * deduce the secret is being used for email confirmation, in which case the TTL
 * is set to 1 day. The key generated for untrusted users also contains no random bytes
 * so that if a user exits their session and comes back, they don't have to immediately
 * generate a new code / email.
 */
class TwoFactorSecretStore extends AbstractTwoFactorSecretStore
{
    /**
     * Constructor.
     * @param ?CacheInterface $cache - PsrWrapper around cache.
     */
    public function __construct(
        private ?CacheInterface $cache = null,
    ) {
        $this->cache ??= Di::_()->get('Cache\Cassandra');
    }

    /**
     * Get secret from store.
     * @param User $user - user for the entry we want to try to retrieve.
     * @return ?TwoFactorSecret - object containing secret.
     * @throws InvalidArgumentException
     */
    public function get(User $user): ?TwoFactorSecret
    {
        return $this->getByKey($this->getKey($user));
    }

    /**
     * Gets entry by key.
     * @param string $key - key to get entry by.
     * @return ?TwoFactorSecret - object containing secret.
     * @throws InvalidArgumentException
     */
    public function getByKey(string $key): ?TwoFactorSecret
    {
        $storedJson = $this->cache->get($key);
        
        try {
            $storedObject = json_decode($storedJson, true);
        } catch (Exception $e) {
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
     * @throws InvalidArgumentException
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
            $this->getTTL($user)
        );

        return $key;
    }

    /**
     * Delete an entry by key.
     * @param string $key - key to delete by.
     * @return TwoFactorSecretStore
     * @throws InvalidArgumentException
     */
    public function delete(string $key): self
    {
        $this->cache->delete($key);
        return $this;
    }
}
