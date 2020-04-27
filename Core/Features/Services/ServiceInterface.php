<?php
/**
 * ServiceInterface
 *
 * @author edgebal
 */

namespace Minds\Core\Features\Services;

use Minds\Entities\User;

interface ServiceInterface
{
    /**
     * Readable name. Used for admin interface.
     * @return string
     */
    public function getReadableName(): string;

    /**
     * Sets the current interface to sync/fetch
     * @param string $environment
     * @return ServiceInterface
     */
    public function setEnvironment(string $environment): ServiceInterface;

    /**
     * Sets the current user to calculate context values
     * @param User|null $user
     * @return ServiceInterface
     */
    public function setUser(?User $user): ServiceInterface;

    /**
     * Synchronizes and caches the service's schema/data, if needed
     * @param int $ttl
     * @return bool
     */
    public function sync(int $ttl): bool;

    /**
     * Fetches the whole feature flag set
     * @param string[] $keys Array of whitelisted keys
     * @return array
     */
    public function fetch(array $keys): array;
}
