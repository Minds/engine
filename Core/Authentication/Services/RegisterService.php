<?php

namespace Minds\Core\Authentication\Services;

use Minds\Entities\User;

class RegisterService
{
    /**
     * Currently a wrapper around register_user.
     */
    public function register(
        string $username,
        string $password,
        string $name,
        string $email,
        bool $validatePassword = true,
        bool $isActivityPub = false
    ): ?User {
        return register_user($username, $password, $username, $email, validatePassword: false, isActivityPub: true);
    }
}
