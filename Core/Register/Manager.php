<?php
namespace Minds\Core\Register;

use Exception;

/**
 * Register Manager
 * @package Minds\Core\Register
 */
class Manager
{
    /**
     * Checks if username has already been taken
     * @param string $username
     * @return boolean
     */
    public function validateUsername(string $username): bool
    {
        if (!$username) {
            throw new Exception("Username required");
        }

        $valid = true;

        if (check_user_index_to_guid($username)) {
            $valid = false;
        }

        return $valid;
    }
}
