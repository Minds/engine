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
     * Sets the current user to calculate context values
     * @param User|null $user
     * @return ServiceInterface
     */
    public function setUser(?User $user): ServiceInterface;

    /**
     * Fetches the whole feature flag set
     * @param string[] $keys Array of whitelisted keys
     * @return array
     */
    public function fetch(array $keys): array;
}
