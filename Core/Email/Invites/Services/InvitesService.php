<?php
declare(strict_types=1);

namespace Minds\Core\Email\Invites\Services;

use Minds\Core\Di\Di;
use Minds\Core\Email\Invites\Enums\InviteEmailStatusEnum;
use Minds\Core\Email\Invites\Repositories\InvitesRepository;
use Minds\Core\Email\Invites\Types\Invite;
use Minds\Core\Email\Invites\Types\InviteConnection;
use Minds\Core\Email\Invites\Types\InviteEdge;
use Minds\Core\Email\V2\Campaigns\Recurring\Invite\InviteEmailer;
use Minds\Core\GraphQL\Types\PageInfo;
use Minds\Core\MultiTenant\Exceptions\NoTenantFoundException;
use Minds\Core\MultiTenant\Services\MultiTenantBootService;
use Minds\Core\Router\Exceptions\ForbiddenException;
use Minds\Core\Security\Rbac\Enums\RolesEnum;
use Minds\Entities\User;
use Minds\Exceptions\NotFoundException;
use Minds\Exceptions\ServerErrorException;

class InvitesService
{
    public function __construct(
        private readonly InvitesRepository $invitesRepository,
    ) {
    }

    /**
     * @param User $user
     * @param string $emails
     * @param string $bespokeMessage
     * @param RolesEnum[]|null $roles
     * @param array|null $groups
     * @return void
     * @throws ServerErrorException
     */
    public function createInvite(
        User   $user,
        string $emails,
        string $bespokeMessage,
        ?array $roles = null,
        ?array $groups = null,
    ): void {
        // TODO: Validate emails, roles
        $this->invitesRepository->createInvite(
            user: $user,
            emails: $this->prepareEmailAddresses($emails),
            bespokeMessage: $bespokeMessage,
            roles: $roles,
            groups: $groups,
        );
    }

    /**
     * Processes a list of emails in string format and returns a list of unique email addresses
     * Note: Allowed separators are comma and newline
     * @param string $emails
     * @return array
     */
    private function prepareEmailAddresses(string $emails): array
    {
        return array_unique(
            array_merge(
                ...array_values(
                    array_map(
                        fn (string $email) => explode(
                            "\n",
                            $email
                        ),
                        explode(',', $emails)
                    )
                )
            )
        );
    }

    /**
     * @param string $inviteToken
     * @return Invite
     * @throws ServerErrorException
     * @throws NotFoundException
     */
    public function getInviteByToken(string $inviteToken): Invite
    {
        return $this->invitesRepository->getInviteByToken($inviteToken);
    }

    /**
     * @param int $first
     * @param string|null $after
     * @param string|null $search
     * @return InviteConnection
     * @throws ServerErrorException
     */
    public function getInvites(
        int     $first,
        ?string $after = null,
        ?string $search = null,
    ): InviteConnection {
        $hasMore = false;
        $invites = $this->invitesRepository->getInvites(
            first: $first,
            after: (int)$after,
            hasMore: $hasMore,
            search: $search,
        );

        return (new InviteConnection())
            ->setEdges(
                array_map(
                    fn (Invite $invite) => new InviteEdge(
                        node: $invite,
                        cursor: (string)$invite->inviteId,
                    ),
                    iterator_to_array($invites)
                )
            )
            ->setPageInfo(
                new PageInfo(
                    hasNextPage: $hasMore,
                    hasPreviousPage: $after !== null,
                    startCursor: !$after ? null : (string)($after ? (int)$after : 0),
                    endCursor: !$hasMore ? null : (string)((int)$after + $first),
                )
            );
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
        $invite = $this->getInviteById($inviteId);

        // resend invite
        $inviteEmailer = new InviteEmailer();
        $inviteEmailer
            ->setInvite($invite)
            ->setSender($sender);

        if (!$inviteEmailer->send()) {
            $this->invitesRepository->updateInviteStatus($invite->inviteId, InviteEmailStatusEnum::FAILED);
        } else {
            $this->invitesRepository->updateInviteStatus($invite->inviteId, InviteEmailStatusEnum::SENT);
        }
    }

    /**
     * @param int $inviteId
     * @return Invite
     * @throws NotFoundException
     * @throws ServerErrorException
     */
    public function getInviteById(int $inviteId): Invite
    {
        return $this->invitesRepository->getInviteById($inviteId);
    }

    /**
     * @param int $inviteId
     * @param InviteEmailStatusEnum $status
     * @return bool
     */
    public function updateInviteStatus(int $inviteId, InviteEmailStatusEnum $status): bool
    {
        return $this->invitesRepository->updateInviteStatus($inviteId, $status);
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
        $entitiesBuilder = Di::_()->get('EntitiesBuilder');
        $inviteEmailer = new InviteEmailer();

        /**
         * @var MultiTenantBootService $multiTenantBootService
         */
        $multiTenantBootService = Di::_()->get(MultiTenantBootService::class);

        foreach ($invites as $invite) {
            if ($invite->tenantId > 0) {
                $multiTenantBootService->bootFromTenantId($invite->tenantId);
            }

            $sender = $entitiesBuilder->single($invite->ownerGuid);

            $this->invitesRepository->updateInviteStatus($invite->inviteId, InviteEmailStatusEnum::SENDING);

            $inviteEmailer
                ->setInvite($invite)
                ->setSender($sender);

            if (!$inviteEmailer->send()) {
                $this->invitesRepository->updateInviteStatus($invite->inviteId, InviteEmailStatusEnum::FAILED);
            } else {
                $this->invitesRepository->updateInviteStatus($invite->inviteId, InviteEmailStatusEnum::SENT);
            }

            if ($invite->tenantId > 0) {
                $multiTenantBootService->resetRootConfigs();
            }
        }
    }
}
