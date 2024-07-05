<?php
declare(strict_types=1);

namespace Minds\Core\Security\Rbac\Services;

use Minds\Core\Config\Config;
use Minds\Core\Data\cache\PsrWrapper;
use Minds\Core\Log\Logger;
use Minds\Core\Security\Rbac\Enums\PermissionIntentTypeEnum;
use Minds\Core\Security\Rbac\Enums\PermissionsEnum;
use Minds\Core\Security\Rbac\Helpers\PermissionIntentHelpers;
use Minds\Core\Security\Rbac\Repositories\PermissionIntentsRepository;

/**
 * Service class for permission intents.
 */
class PermissionIntentsService
{
    /** Cache key. */
    const CACHE_KEY = 'permission_intents';

    public function __construct(
        private readonly PermissionIntentsRepository $repository,
        private readonly PermissionIntentHelpers $permissionIntentHelpers,
        private readonly PsrWrapper $cache,
        private readonly Config $config,
        private readonly Logger $logger
    ) {
    }

    /**
     * Gets permission intents. Will pull from cache for tenants if a cached value is present,
     * and write to cache when values are retrieved.
     * @return array - the permission intents.
     */
    public function getPermissionIntents(): array
    {
        if ((bool) $this->config->get('tenant_id')) {
            // Try to pull value from the cache.
            if ($cachedPermissionIntents = $this->cache->get(self::CACHE_KEY)) {
                try {
                    return unserialize($cachedPermissionIntents);
                } catch (\Exception $e) {
                    $this->logger->error($e);
                }
            }

            // Pull value from DB. On failure return default permission intents.
            try {
                $permissionIntents = iterator_to_array($this->repository->getPermissionIntents());
            } catch (\Exception $e) {
                // In the event of a MySQL error, return the non-tenant defaults as a fallback.
                $this->logger->error($e);
                return $this->permissionIntentHelpers->getNonTenantDefaults();
            }

            // Update cache with retrieved permission intents.
            try {
                $this->cache->set(self::CACHE_KEY, serialize($permissionIntents));
            } catch (\Exception $e) {
                $this->logger->error($e);
            }

            return $permissionIntents;
        }

        return $this->permissionIntentHelpers->getNonTenantDefaults();
    }

    /**
     * Set permission intent value.
     * @param PermissionsEnum $permissionId - the permission ID.
     * @param PermissionIntentTypeEnum $intentType - the intent type.
     * @param string|null $membershipGuid - Any bound membership GUID.
     * @return boolean - true if the operation was successful, false otherwise.
     */
    public function setPermissionIntent(
        PermissionsEnum $permissionId,
        PermissionIntentTypeEnum $intentType,
        ?string $membershipGuid = null
    ): bool {
        $success = $this->repository->upsert(
            permissionId: $permissionId,
            intentType: $intentType,
            membershipGuid: $membershipGuid
        );

        if ($success && (bool) $this->config->get('tenant_id')) {
            try {
                $permissionIntents = $this->getPermissionIntents();

                array_map(function ($permissionIntent) use ($permissionId, $intentType, $membershipGuid) {
                    if ($permissionIntent->permissionId === $permissionId) {
                        $permissionIntent->intentType = $intentType;
                        $permissionIntent->membershipGuid = $membershipGuid ? (int) $membershipGuid : null;
                    }
                }, $permissionIntents);

                $this->cache->set(self::CACHE_KEY, serialize($permissionIntents));
            } catch (\Exception $e) {
                $this->logger->error($e);
            }
        }

        return $success;
    }
}
