<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Services;

use Minds\Core\Email\V2\Delegates\DigestSender;
use Minds\Core\Log\Logger;
use Minds\Core\MultiTenant\Models\Tenant;
use Minds\Interfaces\SenderInterface;

/**
 * Tenant email service.
 */
class TenantEmailService
{
    public function __construct(
        private readonly MultiTenantBootService $multiTenantBootService,
        private readonly MultiTenantDataService $multiTenantDataService,
        private readonly TenantUsersService  $multiTenantUsersService,
        private readonly Logger $logger
    ) {
    }

    /**
     * Send an email to all users of the given tenant.
     * @param Tenant $tenant - the tenant to send the email to.
     * @param SenderInterface $emailSender - the email sender class.
     * @return void
     */
    public function sendToAllUsers(Tenant $tenant, SenderInterface $emailSender): void
    {
        foreach ($this->multiTenantUsersService->getUsers(tenantId: $tenant->id) as $user) {
            try {
                $emailSender->send($user);
                $this->logger->info('Ran for tenant: ' . $tenant?->id. ', user: ' . $user?->getGuid());
            } catch(\Exception $e) {
                $this->logger->error($e);
            }
        }
    }

    /**
     * Send an email to all users across ALL tenants.
     * @param SenderInterface $emailSender - the email sender class.
     * @return bool - true if the emails were sent successfully.
     */
    public function sendToAllUsersAcrossTenants(SenderInterface $emailSender): bool
    {
        foreach ($this->multiTenantDataService->getTenants(limit: 9999999) as $tenant) {
            if ($this->isEmailDisabled(emailSender: $emailSender, tenant: $tenant)) {
                $this->logger->warning("Skipping ".get_class($emailSender)." for tenant: {$tenant->id}");
                continue;
            }

            $this->multiTenantBootService->bootFromTenantId($tenant->id);
            $this->sendToAllUsers($tenant, $emailSender);
        }
        return true;
    }

    /**
     * Check if the email is disabled for the given tenant.
     * @param SenderInterface $emailSender - the email sender class.
     * @param Tenant $tenant - the tenant to check.
     * @return bool - true if the email is disabled for the tenant.
     */
    private function isEmailDisabled(SenderInterface $emailSender, Tenant $tenant)
    {
        switch(true) {
            case $emailSender instanceof DigestSender:
                return !$tenant->config->digestEmailEnabled;
            default:
                return false;
        }
    }
}
