<?php
declare(strict_types=1);

namespace Minds\Core\Email\V2\Campaigns\Recurring\UnreadMessages;

use Minds\Core\Chat\Enums\ChatRoomMemberStatusEnum;
use Minds\Core\Chat\Repositories\ReceiptRepository;
use Minds\Core\Email\V2\Campaigns\Recurring\UnreadMessages\UnreadMessages;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Log\Logger;
use Minds\Core\MultiTenant\Services\MultiTenantBootService;
use Minds\Core\MultiTenant\Services\MultiTenantDataService;
use Minds\Entities\User;

/**
 * Dispatcher for unread messages email.
 */
class UnreadMessagesDispatcher
{
    public function __construct(
        private UnreadMessages $unreadMessagesEmailer,
        private MultiTenantBootService $multiTenantBootService,
        private MultiTenantDataService $multiTenantDataService,
        private ReceiptRepository $receiptRepository,
        private EntitiesBuilder $entitiesBuilder,
        private Logger $logger
    ) {
    }

    /**
     * Dispatch for a given tenant.
     * @param int $tenantId - the ID of the tenant.
     * @param int $createdAfterTimestamp - filter out messages sent before this timestamp.
     * @return void
     */
    public function dispatchForTenant(int $tenantId, int $createdAfterTimestamp): void
    {
        if ($tenantId !== -1) {
            $this->multiTenantBootService->bootFromTenantId($tenantId);
        }
        
        $unreadCountData = $this->receiptRepository->getAllUsersWithUnreadMessages(
            memberStatuses: [ ChatRoomMemberStatusEnum::ACTIVE, ChatRoomMemberStatusEnum::INVITE_PENDING ],
            createdAfterTimestamp: $createdAfterTimestamp
        );

        $this->logger->info("Sending for tenant_id: $tenantId ...");

        foreach($unreadCountData as $data) {
            $user = $this->entitiesBuilder->single($data['user_guid']);

            if (!$user || !($user instanceof User)) {
                $this->logger->info('User not found');
                continue;
            }

            $this->logger->info("Sending to: @" . $user->getUsername());

            $this->unreadMessagesEmailer->withArgs(
                user: $user,
                createdAfterTimestamp:$createdAfterTimestamp
            )->send();
        }

        $this->logger->info("Done sending for tenant_id: $tenantId");
    }
}
