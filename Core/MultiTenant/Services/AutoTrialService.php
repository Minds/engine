<?php
namespace Minds\Core\MultiTenant\Services;

use Minds\Core\Authentication\Services\RegisterService;
use Minds\Core\Email\V2\Campaigns\Recurring\TenantTrial\TenantTrialEmailer;
use Minds\Core\Guid;
use Minds\Core\MultiTenant\Enums\TenantUserRoleEnum;
use Minds\Core\MultiTenant\Models\Tenant;
use Minds\Core\MultiTenant\Types\TenantUser;
use Minds\Entities\User;
use Minds\Exceptions\UserErrorException;

class AutoTrialService
{
    public function __construct(
        private RegisterService $registerService,
        private TenantsService $tenantsService,
        private TenantUsersService $usersService,
        private TenantTrialEmailer $emailService,
    ) {
        
    }
    public function startTrialWithEmail(
        string $email
    ): Tenant {
        // An ephemeral 'fake' account that is never mADE
        $user = new User();
        $user->username = 'networkadmin';
        $user->setEmail($email);

        // Create a trial
        $tenant = $this->tenantsService->createNetworkTrial(new Tenant(
            id: -1, // Fake id
            ownerGuid: -1, // Not really owned by a Minds user
        ), $user);

        // Generate a temorary password we will share with the customer
        $password = substr(hash('sha1', openssl_random_pseudo_bytes(256)), 0, 8);
        
        // Create the root user
        $this->usersService->createNetworkRootUser(
            networkUser: new TenantUser(
                guid: (int) Guid::build(),
                username: $user->username,
                tenantId: $tenant->id,
                role: TenantUserRoleEnum::OWNER,
                plainPassword: $password,
            ),
            sourceUser: $user
        );

        // Send an email with a the username and password to login to the tenant
        $this->emailService->setUser($user)
            ->setTenantId($tenant->id)
            ->setUsername($user->username)
            ->setPassword($password)
            ->send();

        return $tenant;
    }

}
