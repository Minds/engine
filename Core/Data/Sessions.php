<?php
/**
 * The session storage handler
 */

namespace Minds\Core\Data;

use Minds\Core\Data\cache\Redis;
use Minds\Core;

class Sessions
{
    /**
     * Gets user's GUID from session (if exists)
     * @return mixed
     */
    protected function getUserGuid()
    {
        return Core\Session::getLoggedInUserGuid();
    }
}
