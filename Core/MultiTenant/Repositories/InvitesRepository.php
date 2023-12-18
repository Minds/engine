<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Repositories;

use Minds\Core\Config\Config;
use Minds\Core\Data\MySQL\AbstractRepository;
use Minds\Core\Data\MySQL\Client;
use Minds\Core\Log\Logger;
use Minds\Core\MultiTenant\Enums\TenantInviteEmailStatusEnum;
use Minds\Core\MultiTenant\Enums\TenantInviteStatusEnum;
use Minds\Entities\User;
use Minds\Exceptions\ServerErrorException;
use Selective\Database\RawExp;

class InvitesRepository extends AbstractRepository
{
    public function __construct(
        Client                  $mysqlHandler,
        Logger                  $logger,
        private readonly Config $config
    ) {
        parent::__construct($mysqlHandler, $logger);
    }

    /**
     * @param User $user
     * @param array $emails
     * @param array $roles
     * @param string $bespokeMessage
     * @param array $groups
     * @return void
     * @throws ServerErrorException
     */
    public function createInvite(
        User   $user,
        array  $emails,
        string $bespokeMessage,
        ?array $roles = null,
        ?array $groups = null,
    ): void {
        $stmt = $this->mysqlClientWriterHandler->insert()
            ->into('minds_tenant_invites')
            ->set([
                'tenant_id' => $this->config->get('tenant_id') ?? -1,
                'owner_guid' => (int)$user->getGuid(),
                'target_roles' => new RawExp(":roles"),
                'target_group_guids' => new RawExp(":groups"),
                'custom_message' => $bespokeMessage,
                'status' => TenantInviteStatusEnum::PENDING->value,
            ])->prepare();

        $this->mysqlHandler->bindValuesToPreparedStatement($stmt, [
            'roles' => $roles ? implode(',', $roles) : null,
            'groups' => $groups ? implode(',', $groups) : null,
        ]);

        if (!$stmt->execute()) {
            throw new ServerErrorException("Failed to create invite");
        }

        $this->beginTransaction();

        $stmt = $this->mysqlClientWriterHandler->insert()
            ->into('minds_tenant_invite_emails')
            ->set([
                'invite_id' => $this->mysqlClientWriter->lastInsertId(),
                'email' => new RawExp(":email"),
                'status' => TenantInviteEmailStatusEnum::PENDING->value,
            ])->prepare();

        foreach ($emails as $email) {
            if (!$stmt->execute(['email' => $email])) {
                $this->rollbackTransaction();
                throw new ServerErrorException("Failed to create invite");
            }
        }

        $this->commitTransaction();
    }
}
