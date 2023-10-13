<?php
/**
 * ActiveSession
 *
 * @author edgebal
 */

namespace Minds\Core\Sessions;

use Minds\Core\Session as CoreSession;
use Minds\Entities\User;

/**
 * Allow using an instance-based dependency to retrieve
 * the currently logged-in user
 * @package Minds\Core\Sessions
 */
class ActiveSession
{
    /**
     * Gets the currently logged in user
     * @return User|null
     */
    public function getUser(): ?User
    {
        return CoreSession::getLoggedinUser() ?: null;
    }

    /**
     * Gets the currently logged in user guid
     * @return int|null
     */
    public function getUserGuid(): ?int
    {
        $user = $this->getUser();

        return $user ? (int) $user->getGuid() : null;
    }
}
