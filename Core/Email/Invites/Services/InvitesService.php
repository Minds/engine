<?php
declare(strict_types=1);

namespace Minds\Core\Email\Invites\Services;

use Minds\Core\Email\Invites\Repositories\InvitesRepository;
use Minds\Core\Email\Invites\Types\Invite;
use Minds\Core\Email\Invites\Types\InviteConnection;
use Minds\Core\Email\Invites\Types\InviteEdge;
use Minds\Core\GraphQL\Types\PageInfo;
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
}
