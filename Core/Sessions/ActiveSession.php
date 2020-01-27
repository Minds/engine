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
}
