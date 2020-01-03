<?php
/**
 * UserFactory.
 *
 * @author edgebal
 */

namespace Minds\Entities;

use Exception;

class UserFactory
{
    /**
     * @param null $guid
     * @param bool $cache
     * @return User|null
     */
    public function build($guid = null, bool $cache = true): ?User
    {
        try {
            return new User($guid, $cache);
        } catch (Exception $e) {
            error_log((string) $e);
            return null;
        }
    }
}
