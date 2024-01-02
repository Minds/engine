<?php
declare(strict_types=1);

namespace Minds\Core\Email\Invites\Services;

use Minds\Core\Di\Di;
use Minds\Core\Email\Invites\Enums\InviteEmailStatusEnum;
use Minds\Core\Email\Invites\Repositories\InvitesRepository;
use Minds\Core\Email\V2\Campaigns\Recurring\Invite\InviteEmailer;
use Minds\Core\EntitiesBuilder;
use Minds\Core\MultiTenant\Exceptions\NoTenantFoundException;
use Minds\Core\MultiTenant\Services\MultiTenantBootService;
use Minds\Core\Router\Exceptions\ForbiddenException;
use Minds\Entities\User;
use Minds\Exceptions\NotFoundException;
use Minds\Exceptions\ServerErrorException;

class InviteSenderService
{
    public function __construct(
        private readonly EntitiesBuilder        $entitiesBuilder,
        private readonly MultiTenantBootService $multiTenantBootService,
        private readonly InvitesRepository      $invitesRepository,
        private readonly InviteEmailer          $inviteEmailer,
    ) {
    }

    /**
     * @return void
     * @throws ForbiddenException
     * @throws NoTenantFoundException
     */
    public function sendInvites(): void
    {
        if (php_sapi_name() !== 'cli') {
            throw new ForbiddenException('This endpoint is restricted');
        }

        $invites = $this->invitesRepository->getInvitesToSend();

        /**
         * @var MultiTenantBootService $multiTenantBootService
         */
        $multiTenantBootService = Di::_()->get(MultiTenantBootService::class);

        foreach ($invites as $invite) {
            if ($invite->tenantId > 0) {
                $this->multiTenantBootService->bootFromTenantId($invite->tenantId);
            }

            $sender = $this->entitiesBuilder->single($invite->ownerGuid);

            $this->invitesRepository->updateInviteStatus($invite->inviteId, InviteEmailStatusEnum::SENDING);

            $this->inviteEmailer
                ->setInvite($invite)
                ->setSender($sender);

            if (!$this->inviteEmailer->send()) {
                $this->invitesRepository->updateInviteStatus($invite->inviteId, InviteEmailStatusEnum::FAILED);
            } else {
                $this->invitesRepository->updateInviteStatus($invite->inviteId, InviteEmailStatusEnum::SENT);
            }

            if ($invite->tenantId > 0) {
                $multiTenantBootService->resetRootConfigs();
            }
        }
    }


    /**
     * @param int $inviteId
     * @param User $sender
     * @return void
     * @throws NotFoundException
     * @throws ServerErrorException
     */
    public function resendInvite(int $inviteId, User $sender): void
    {
        $invite = $this->invitesRepository->getInviteById($inviteId);

        // resend invite
        $this->inviteEmailer
            ->setInvite($invite)
            ->setSender($sender);

        if (!$this->inviteEmailer->send()) {
            $this->invitesRepository->updateInviteStatus($invite->inviteId, InviteEmailStatusEnum::FAILED);
        } else {
            $this->invitesRepository->updateInviteStatus($invite->inviteId, InviteEmailStatusEnum::SENT);
        }
    }
}
