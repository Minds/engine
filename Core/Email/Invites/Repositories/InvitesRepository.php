<?php
declare(strict_types=1);

namespace Minds\Core\Email\Invites\Repositories;

use Exception;
use Minds\Common\Jwt;
use Minds\Core\Config\Config;
use Minds\Core\Data\MySQL\AbstractRepository;
use Minds\Core\Data\MySQL\Client;
use Minds\Core\Email\Invites\Enums\InviteEmailStatusEnum;
use Minds\Core\Email\Invites\Types\Invite;
use Minds\Core\Log\Logger;
use Minds\Core\Security\Rbac\Enums\RolesEnum;
use Minds\Entities\User;
use Minds\Exceptions\NotFoundException;
use Minds\Exceptions\ServerErrorException;
use PDO;
use Selective\Database\Operator;
use Selective\Database\RawExp;

class InvitesRepository extends AbstractRepository
{
    public function __construct(
        Client               $mysqlHandler,
        Logger               $logger,
        Config               $config,
        private readonly Jwt $jwt
    ) {
        parent::__construct($mysqlHandler, $config, $logger);
    }

    /**
     * @param User $user
     * @param array $emails
     * @param string $bespokeMessage
     * @param RolesEnum[]|null $roles
     * @param array|null $groups
     * @return void
     * @throws ServerErrorException
     * @throws Exception
     */
    public function createInvite(
        User   $user,
        array  $emails,
        string $bespokeMessage,
        ?array $roles = null,
        ?array $groups = null,
    ): void {
        $this->beginTransaction();

        $stmt = $this->mysqlClientWriterHandler->insert()
            ->into('minds_tenant_invites')
            ->set([
                'email' => new RawExp(":email"),
                'tenant_id' => $this->config->get('tenant_id') ?? -1,
                'owner_guid' => (int)$user->getGuid(),
                'invite_token' => new RawExp(":token"),
                'target_roles' => new RawExp(":roles"),
                'target_group_guids' => new RawExp(":groups"),
                'custom_message' => $bespokeMessage,
                'status' => InviteEmailStatusEnum::PENDING->value,
            ])
            ->onDuplicateKeyUpdate([
                'created_timestamp' => new RawExp('created_timestamp'),
            ])
            ->prepare();

        foreach ($emails as $email) {
            if (
                !$stmt->execute([
                    'email' => $email,
                    'roles' => $roles ? implode(',', array_map(fn (int $role): int => RolesEnum::from($role)->value, $roles)) : null,
                    'groups' => $groups ? implode(',', $groups) : null,
                    'token' => $this->generateInviteToken($email, $this->config->get('tenant_id') ?? -1),
                ])
            ) {
                $this->rollbackTransaction();
                throw new ServerErrorException("Failed to create invite");
            }
        }

        $this->commitTransaction();
    }

    /**
     * @param int $inviteId
     * @return string
     * @throws Exception
     */
    private function generateInviteToken(string $email, int $tenantId): string
    {
        return $this->jwt->encode(
            payload: [
                'email' => $email,
                'tenant_id' => $tenantId,
            ]
        );
    }

    /**
     * @param string $inviteToken
     * @return Invite
     * @throws NotFoundException
     * @throws ServerErrorException
     * @throws Exception
     */
    public function getInviteByToken(string $inviteToken): Invite
    {
        $statement = $this->mysqlClientReaderHandler->select()
            ->from('minds_tenant_invites')
            ->where('invite_token', Operator::EQ, new RawExp(":token"))
            ->where('tenant_id', Operator::EQ, $this->config->get('tenant_id') ?? -1)
            ->prepare();

        if (!$statement->execute(['token' => $inviteToken])) {
            throw new ServerErrorException("Failed to fetch invite");
        }

        if ($statement->rowCount() === 0) {
            throw new NotFoundException("Invite not found");
        }

        return $this->buildInvite($statement->fetch(PDO::FETCH_ASSOC));
    }

    private function buildInvite(array $data): Invite
    {
        return new Invite(
            inviteId: $data['id'],
            tenantId: $data['tenant_id'],
            ownerGuid: $data['owner_guid'],
            email: $data['email'],
            inviteToken: $data['invite_token'],
            status: InviteEmailStatusEnum::from((int)$data['status']),
            bespokeMessage: $data['custom_message'],
            createdTimestamp: (int)strtotime($data['created_timestamp']),
            sendTimestamp: $data['send_timestamp'] ? strtotime($data['send_timestamp']) : null,
            roles: $data['target_roles'] ? array_map(fn (string $role): int => (int)$role, explode(',', $data['target_roles'])) : null,
            groups: $data['target_group_guids'] ? array_map(fn (string $groupGuid): int => (int)$groupGuid, explode(',', $data['target_group_guids'])) : null,
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
        $statement = $this->mysqlClientReaderHandler->select()
            ->from('minds_tenant_invites')
            ->where('id', Operator::EQ, new RawExp(":id"))
            ->where('tenant_id', Operator::EQ, $this->config->get('tenant_id') ?? -1)
            ->prepare();

        if (!$statement->execute(['id' => $inviteId])) {
            throw new ServerErrorException("Failed to fetch invite");
        }

        if ($statement->rowCount() === 0) {
            throw new NotFoundException("Invite not found");
        }

        return $this->buildInvite($statement->fetch(PDO::FETCH_ASSOC));
    }

    /**
     * @param int $first
     * @param int $after
     * @param bool $hasMore
     * @param string|null $search
     * @return Invite[]
     * @throws ServerErrorException
     */
    public function getInvites(
        int     $first,
        int     $after,
        bool    &$hasMore,
        ?string $search = null,
    ): iterable {
        $statement = $this->mysqlClientReaderHandler->select()
            ->from('minds_tenant_invites')
            ->where('tenant_id', Operator::EQ, $this->config->get('tenant_id') ?? -1)
            ->where('status', Operator::IN, [InviteEmailStatusEnum::PENDING->value, InviteEmailStatusEnum::SENT->value, InviteEmailStatusEnum::FAILED->value])
            ->orderBy('send_timestamp DESC', 'created_timestamp DESC')
            ->limit($first + 1)
            ->offset($after);
        $values = [];

        if ($search) {
            $statement->where('email', Operator::LIKE, new RawExp(":search"));
            $values['search'] = "%$search%";
        }

        $statement = $statement->prepare();

        if (!$statement->execute($values)) {
            throw new ServerErrorException("Failed to fetch invites");
        }

        $hasMore = $statement->rowCount() === $first + 1;

        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $index => $invite) {
            if ($index === $first) {
                break;
            }

            yield $this->buildInvite($invite);
        }
    }

    /**
     * @param int $inviteId
     * @param InviteEmailStatusEnum $status
     * @return bool
     */
    public function updateInviteStatus(
        int                   $inviteId,
        InviteEmailStatusEnum $status
    ): bool {
        $setColumns = [
            'status' => $status->value,
        ];
        if ($status === InviteEmailStatusEnum::SENT) {
            $setColumns['send_timestamp'] = new RawExp('NOW()');
        }

        $stmt = $this->mysqlClientWriterHandler->update()
            ->table('minds_tenant_invites')
            ->set($setColumns)
            ->where('id', Operator::EQ, $inviteId)
            ->where('tenant_id', Operator::EQ, $this->config->get('tenant_id') ?? -1)
            ->prepare();

        return $stmt->execute();
    }

    /**
     * Returns the oldest 1000 invites that have not been sent yet
     * @return Invite[]
     */
    public function getInvitesToSend(): iterable
    {
        $statement = $this->mysqlClientReaderHandler->select()
            ->from('minds_tenant_invites')
            ->where('status', Operator::EQ, InviteEmailStatusEnum::PENDING->value)
            ->orWhere('status', Operator::EQ, InviteEmailStatusEnum::FAILED->value)
            ->orderBy('created_timestamp ASC')
            ->limit(1000)
            ->execute();

        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $invite) {
            yield $this->buildInvite($invite);
        }
    }
}
