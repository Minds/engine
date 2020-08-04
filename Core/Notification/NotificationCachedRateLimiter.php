<?php
/**
 * NotificationCachedRateLimiter -
 *
 * Determines whether a user has already triggered this notification type
 * in the last X amount of time, by checking if the value already exists in cache
 * and saving a new value to the cache for subsequent calls if one does not exist.
 *
 * To extend to add more notification types,
 * simply add mapping to the class level variable cacheKeyMap.
 *
 * @author Ben Hayward
 */
namespace Minds\Core\Notification;

use Minds\Entities\Factory;
use Minds\Core\Di\Di;

class NotificationCachedRateLimiter
{
    private $notification;
    private $cache;

    /**
     * @var array mapping notification types to their cache string counterpart.
     */
    private $cacheKeyMap = [
        'tag' => 'recently-tagged',
        'downvote' => 'recently-downvoted',
        'friends' => 'recently-subscribed',
    ];
    
    public function __construct($cache = null, $logger = null)
    {
        $this->cache = $cache ?? Di::_()->get('Cache');
        $this->logger = $logged ?? Di::_()->get('Logger');
    }

    /**
     * Set the notification of the class.
     * @param $notification - Notification object.
     * @return NotificationCachedRateLimiter -Chainable.
     */
    public function setNotification($notification): NotificationCachedRateLimiter
    {
        $this->notification = $notification;
        return $this;
    }

    /**
     * Determines whether a limit should be imposed.
     * @return boolean true if limit should be imposed.
     */
    public function shouldImposeLimit(): bool
    {
        if (!$this->notification) {
            throw new \Exception('No notification set for NotificationCachedRateLimiter');
        }
        try {
            $toUser = Factory::build($this->notification->getToGuid());
            $type = $this->notification->getType();
            $cacheKey = $this->generateCacheKey($type);

            // only if cacheKey value mapping is specified, and if user is not subscribed.
            if ($cacheKey && $cacheKey !== '' &&
                !$toUser->isSubscribed($this->notification->getFromGuid())
            ) {
                if ($this->cacheKeyExists($cacheKey)) {
                    return true;
                }
                $this->setCacheKey($cacheKey);
            }
            return false;
        } catch (\Exception $e) {
            $this->logger->error($e);
            return false; // do not impose limit if there is an error.
        }
    }

    /**
     * Generates a cache key using the classes $cacheKeyMap.
     * @param string $type - type of notification.
     * @return string - generated cache key.
     */
    private function generateCacheKey($type): string
    {
        if ($this->cacheKeyMap[$type]) {
            return "{$this->notification->getFromGuid()}:{$this->cacheKeyMap[$type]}:{$this->notification->getToGuid()}";
        }
        return '';
    }

    /**
     * Sets entry in cache at given hash key.
     * @param string $cacheKey - Key to add to cache.
     * @return NotificationCachedRateLimiter - Chainable.
     */
    private function setCacheKey($cacheKey): NotificationCachedRateLimiter
    {
        $this->cache->set($cacheKey, true, 60 * 15); // 15 minutes
        return $this;
    }

    /**
     * Gets from cache by a given cache key.
     * @param string $cacheKey - Key to search
     * @return boolean true if cache key exists
     */
    private function cacheKeyExists($cacheKey): bool
    {
        return (bool) $this->cache->get($cacheKey);
    }
}
