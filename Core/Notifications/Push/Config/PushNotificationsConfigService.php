<?php
namespace Minds\Core\Notifications\Push\Config;

/**
 * This service will return the details required in order to deliver (APNS) notifications
 * It is being used by both tenants and the main minds app.
 */
class PushNotificationsConfigService
{
    /** @var array<int,PushNotificationConfig> */
    protected $cache = [];

    public function __construct(
        private PushNotificationsConfigRepository $repository,
    ) {
        
    }

    /**
     * Returns configs for push notifications
     */
    public function get(int $tenantId, bool $useCache = true): ?PushNotificationConfig
    {
        if ($useCache && isset($this->cache[$tenantId])) {
            return $this->cache[$tenantId];
        }

        return $this->cache[$tenantId] = $this->repository->get($tenantId);
    }

}
