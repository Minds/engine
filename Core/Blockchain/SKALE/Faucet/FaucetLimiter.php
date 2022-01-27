<?php
namespace Minds\Core\Blockchain\SKALE\Faucet;

use Minds\Core\Di\Di;
use Minds\Entities\User;
use Minds\Core\Security\RateLimits\KeyValueLimiter;
use Minds\Exceptions\ServerErrorException;

/**
 * Rate limiter for the faucet - limits by user guid, eth address and phone number hash.
 * @package Minds\Core\Blockchain\SKALE\Faucet
 */
class FaucetLimiter
{
    // key for cache, to be interpolated with key id.
    const CACHE_KEY = 'skale:faucet:%s';

    // seconds in a week.
    const SINGLE_WEEK_SECONDS = 604800;

    // max requests per period.
    const RATE_LIMIT_MAX = 1;

    /**
     * FaucetLimiter constructor.
     * @param KeyValueLimiter|null $kvLimiter - key value limiter.
     */
    public function __construct(private ?KeyValueLimiter $kvLimiter = null)
    {
        $this->kvLimiter = $kvLimiter ?? Di::_()->get('Security\RateLimits\KeyValueLimiter');
    }

    /**
     * Check rate limit and increment amount.
     * @param User $user - user to check.
     * @param string $address - address to check.
     * @throws RateLimitExceededException - in event rate limit is exceeded.
     * @return boolean true if rate limit is not exceeded.
     */
    public function checkAndIncrement(User $user, string $address): bool
    {
        if (!$address || !$user) {
            throw new ServerErrorException('Missing params for SKALE faucet rate limiter check');
        }

        $this->checkUserGuidLimit($user)
            ->checkAddressLimit($address)
            ->checkPhoneHashLimit($user);

        return true;
    }

    /**
     * Checks limit for user guid for a given user.
     * @param User $user - user to check limit for
     * @throws RateLimitExceededException - in event rate limit is exceeded.
     * @return self - chainable.
     */
    private function checkUserGuidLimit(User $user): self
    {
        $userCacheKey = sprintf(self::CACHE_KEY, $user->getGuid());

        $this->kvLimiter
            ->setKey($userCacheKey)
            ->setValue(true)
            ->setSeconds(self::SINGLE_WEEK_SECONDS)
            ->setMax(self::RATE_LIMIT_MAX)
            ->checkAndIncrement();

        return $this;
    }

    /**
     * Checks limit for a given Eth address.
     * @param string $address - eth address to check limit for.
     * @throws RateLimitExceededException - in event rate limit is exceeded.
     * @return self - chainable.
     */
    private function checkAddressLimit(string $address): self
    {
        $addressCacheKey = sprintf(self::CACHE_KEY, $address);

        $this->kvLimiter
            ->setKey($addressCacheKey)
            ->setValue(true)
            ->setSeconds(self::SINGLE_WEEK_SECONDS)
            ->setMax(self::RATE_LIMIT_MAX)
            ->checkAndIncrement();

        return $this;
    }

    /**
     * Checks limit for a phone number hash for a given user.
     * @param string $address - user to check phone number hash of.
     * @throws RateLimitExceededException - in event rate limit is exceeded.
     * @throws ServerErrorException - if no phone hash is set.
     * @return self - chainable.
     */
    private function checkPhoneHashLimit(User $user): self
    {
        $phoneNumberHash = $user->getPhoneNumberHash();

        if (!$phoneNumberHash) {
            throw new ServerErrorException('User must be registered for rewards');
        }

        $phoneHashCacheKey = sprintf(self::CACHE_KEY, $phoneNumberHash);

        $this->kvLimiter
            ->setKey($phoneHashCacheKey)
            ->setValue(true)
            ->setSeconds(self::SINGLE_WEEK_SECONDS)
            ->setMax(self::RATE_LIMIT_MAX)
            ->checkAndIncrement();

        return $this;
    }

    /**
     * Removes all faucet limiter cache keys for this user.
     * @param User $user - the user to remove for.
     * @param string $address - address of the user.
     * @return self - chainable.
     */
    public function removeCacheKeys(User $user, string $address): self
    {
        $phoneNumberHash = $user->getPhoneNumberHash();

        $phoneHashCacheKey = sprintf(self::CACHE_KEY, $phoneNumberHash);
        $addressCacheKey = sprintf(self::CACHE_KEY, $address);
        $userCacheKey = sprintf(self::CACHE_KEY, $user->getGuid());

        if ($phoneHashCacheKey) {
            $this->kvLimiter
                ->setKey($phoneHashCacheKey)
                ->setSeconds(self::SINGLE_WEEK_SECONDS)
                ->delete();
        }

        $this->kvLimiter
            ->setKey($addressCacheKey)
            ->setSeconds(self::SINGLE_WEEK_SECONDS)
            ->delete();
        
        $this->kvLimiter
            ->setKey($userCacheKey)
            ->setSeconds(self::SINGLE_WEEK_SECONDS)
            ->delete();

        return $this;
    }
}
