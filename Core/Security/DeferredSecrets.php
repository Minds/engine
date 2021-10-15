<?php

namespace Minds\Core\Security;

use Minds\Core\Di\Di;
use Minds\Entities\User;
use Minds\Exceptions\UserErrorException;

/**
 * Used to generate a secret that is stored in cache.
 * Verification checks the passed in value against the cached secret.
 */
class DeferredSecrets
{
    /** @var Redis */
    private $redis;

    /** @var Logger */
    private $logger;

    public function __construct(
        $redis = null,
        $logger = null
    ) {
        $this->redis = $redis ?? Di::_()->get('Redis');
        $this->logger = $logger ?? Di::_()->get('Logger');
    }

    /**
     * Generate a secret and store it in the cache.
     * @param User $user - user to generate secret for.
     * @return string the generated secret.
     */
    public function generate(User $user): string
    {
        if (!$user) {
            throw new UserErrorException("User must be provided");
        }

        // generate random bytes and convert them to hex.
        $randomBytes = openssl_random_pseudo_bytes(128);
        $secret = bin2hex($randomBytes);

        // store in cache and return secret.
        $this->redis->set($this->getCacheKey($user), $secret, 300);
        return $secret;
    }

    /**
     * Verify a secret given to a user.
     * @param string $secret - the secret to verify.
     * @param User $user - user to verify for.
     * @return boolean true if secret matches cached secret.
     */
    public function verify(string $secret, User $user): bool
    {
        if (!$user) {
            throw new UserErrorException("User must be provided");
        }

        if (!$secret) {
            $this->logger->error('Deferred secret verification attempted without a secret by: ' . $user->getGuid());
            throw new UserErrorException("Missing secret for authorization");
        }

        // get secret from cache.
        $cachedSecret = $this->redis->get($this->getCacheKey($user));

        // delete secret once we have it.
        $this->redis->delete($this->getCacheKey($user));

        // check if user has a cached secret and if the secret they provided is matching.
        return $cachedSecret && $secret === $cachedSecret;
    }


    /**
     * Gets cache key for a user.
     * @param User $user - user to get cache key for.
     * @return string cache key.
     */
    private function getCacheKey(User $user): string
    {
        return  'deferred-secret:'.$user->getGuid();
    }
}
