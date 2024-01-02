<?php
declare(strict_types=1);

namespace Minds\Core\Email\Invites\Services;

use Minds\Core\Email\Invites\Repositories\InvitesRepository;
use Minds\Core\Email\Invites\Types\Invite;
use Minds\Core\Email\Invites\Types\InviteConnection;
use Minds\Core\Email\Invites\Types\InviteEdge;
use Minds\Core\GraphQL\Types\PageInfo;
use Minds\Exceptions\NotFoundException;
use Minds\Exceptions\ServerErrorException;

class InviteReaderService
{
    public function __construct(
        private readonly InvitesRepository $invitesRepository,
    ) {
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
     * @return Invite
     * @throws NotFoundException
     * @throws ServerErrorException
     */
    public function getInviteById(int $inviteId): Invite
    {
        return $this->invitesRepository->getInviteById($inviteId);
    }
}
