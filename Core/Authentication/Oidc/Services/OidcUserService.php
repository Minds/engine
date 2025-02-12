<?php
namespace Minds\Core\Authentication\Oidc\Services;

use Minds\Core\Authentication\Oidc\Repositories\OidcUserRepository;
use Minds\Core\Config\Config;
use Minds\Core\Email\V2\Campaigns\Recurring\TenantUserWelcome\TenantUserWelcomeEmailer;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Log\Logger;
use Minds\Core\Queue\LegacyClient;
use Minds\Core\Security\ACL;
use Minds\Core\Channels\Ban as ChannelBanService;
use Minds\Entities\User;
use Minds\Exceptions\NotFoundException;
use RegistrationException;

class OidcUserService
{
    public function __construct(
        private OidcUserRepository $oidcUserRepository,
        private EntitiesBuilder $entitiesBuilder,
        private ACL $acl,
        private LegacyClient $registerQueue,
        private TenantUserWelcomeEmailer $tenantUserWelcomeEmailer,
        private Config $config,
        private Logger $logger,
        private ChannelBanService $channelBanService,
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
        $this->acl->setIgnore(true);

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

        $password = bin2hex(openssl_random_pseudo_bytes(128));
        $user = register_user($preferredUsername, $password, $displayName, $email, validatePassword: false);

        // Link this user to the oidc map
        $this->oidcUserRepository->linkSubToUserGuid($sub, $providerId, (int) $user->getGuid());

        try {
            if((bool) $this->config->get('tenant_id')) {
                $this->tenantUserWelcomeEmailer
                    ->setUser($user)
                    ->queue($user);
            }
        } catch(\Exception $e) {
            $this->logger->error($e);
        }
   
        $this->registerQueue->send([
            'user_guid' => (string) $user->getGuid(),
            'invite_token' => null,
        ]);

        return $user;
    }

    /**
     * Suspends a user from their oidc id
     */
    public function suspendUserFromSub(string $sub, int $providerId): bool
    {
        $user = $this->getUserFromSub($sub, $providerId);

        if (!$user instanceof User) {
            throw new NotFoundException();
        }

        $ia = $this->acl->setIgnore(true);
        $success =  $this->channelBanService->setUser($user)->ban();
        $this->acl->setIgnore($ia);

        return $success;
    }
}
