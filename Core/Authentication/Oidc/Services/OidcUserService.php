<?php
namespace Minds\Core\Authentication\Oidc\Services;

use Minds\Core\Authentication\Oidc\Repositories\OidcUserRepository;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Security\ACL;
use Minds\Entities\User;
use RegistrationException;

class OidcUserService
{
    public function __construct(
        private OidcUserRepository $oidcUserRepository,
        private EntitiesBuilder $entitiesBuilder,
    ) {
        
    }

    /**
     * Returns a user (if available) from their sub and provider id
     */
    public function getUserFromSub(
        string $sub,
        int $providerId,
    ): ?User {
        $userGuid = $this->oidcUserRepository->getUserGuidFromSub($sub, $providerId);

        if (!$userGuid) {
            return null;
        }

        $user = $this->entitiesBuilder->single($userGuid);

        return $user;
    }


    /**
     * Registers a user and links their to the their oidc sub
     */
    public function register(
        string $sub,
        int $providerId,
        string $preferredUsername,
        string $displayName,
        string $email,
    ): ?User {
        ACL::_()->setIgnore(true);

        try {
            validate_username($preferredUsername);
        } catch (RegistrationException) {
            // An invalid username was passed. We will try and create one for the user.
            $preferredUsername = substr(md5((string) time()), 0, 8);
        }

        // If a user with our preferredUsername already exists, then we need to find a new one
        if (check_user_index_to_guid(strtolower($preferredUsername))) {
            return $this->register($sub, $providerId, $preferredUsername . '_' . rand(0, 999), $displayName, $email);
        }

        $password = openssl_random_pseudo_bytes(128);
        $user = register_user($preferredUsername, $password, $displayName, $email, validatePassword: false);

        // Link this user to the oidc map
        $this->oidcUserRepository->linkSubToUserGuid($sub, $providerId, (int) $user->getGuid());

        return $user;
    }
}
