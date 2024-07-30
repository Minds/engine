<?php
declare(strict_types=1);

namespace Minds\Core\Security\Rbac\Repositories;

use Minds\Core\Data\MySQL\AbstractRepository;
use Minds\Core\Security\Rbac\Enums\PermissionIntentTypeEnum;
use Minds\Core\Security\Rbac\Enums\PermissionsEnum;
use Minds\Core\Security\Rbac\Helpers\PermissionIntentHelpers;
use Minds\Core\Security\Rbac\Models\PermissionIntent;
use PDO;
use Selective\Database\Operator;
use Selective\Database\RawExp;

/**
 * Repository for permission intents.
 */
class PermissionIntentsRepository extends AbstractRepository
{
    /** The table name. */
    const TABLE_NAME = 'minds_tenant_permission_intents';

    public function __construct(
        private ?PermissionIntentHelpers $permissionIntentHelpers = null,
        ... $args
    ) {
        parent::__construct(...$args);
    }

    /**
     * Get the permission intents for a tenant.
     * @param integer|null $tenantId - the tenant ID.
     * @return iterable - the permission intents.
     */
    public function getPermissionIntents(int $tenantId = null): iterable
    {
        if (!$tenantId) {
            $tenantId = $this->config->get('tenant_id') ?? -1;
        }

        $query = $this->mysqlClientReaderHandler->select()
            ->from(self::TABLE_NAME)
            ->columns([
                'tenant_id',
                'permission_id',
                'intent_type',
                'membership_guid'
            ])
            ->where('tenant_id', Operator::EQ, $tenantId);

        $stmt = $query->execute();

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Ensure that we have missing values added and items are in the correct order.
        foreach ($this->permissionIntentHelpers::CONTROLLABLE_PERMISSION_IDS as $controllablePermissionId) {
            $resultIntent = array_values(array_filter(
                $results,
                fn ($result) => $result['permission_id'] === $controllablePermissionId->name,
            ))[0] ?? null;

            $intentType = isset($resultIntent['intent_type']) ?
                PermissionIntentTypeEnum::tryFrom($resultIntent['intent_type']) :
                $this->permissionIntentHelpers->getTenantDefaultIntentType($controllablePermissionId);

            yield new PermissionIntent(
                permissionId: $controllablePermissionId,
                intentType: $intentType,
                membershipGuid: $resultIntent['membership_guid'] ?? null
            );
        }
    }

    /**
     * Upsert a permission intent.
     * @param PermissionsEnum $permissionId - the permission ID.
     * @param PermissionIntentTypeEnum $intentType - the type of the permission intent.
     * @param string|null $membershipGuid - any membership guid bound to the intent.
     * @param integer|null $tenantId - the tenant ID.
     * @return boolean - whether the upsert was successful.
     */
    public function upsert(
        PermissionsEnum $permissionId,
        PermissionIntentTypeEnum $intentType,
        ?string $membershipGuid = null,
        int $tenantId = null
    ): bool {
        if (!$tenantId) {
            $tenantId = $this->config->get('tenant_id') ?? -1;
        }

        $query = $this->mysqlClientWriterHandler->insert()
            ->into(self::TABLE_NAME)
            ->set([
                'tenant_id' => new RawExp(':tenant_id'),
                'permission_id' => new RawExp(':permission_id'),
                'intent_type' => new RawExp(':intent_type'),
                'membership_guid' => new RawExp(':membership_guid')
            ])
            ->onDuplicateKeyUpdate([
                'intent_type' => new RawExp(':intent_type'),
                'membership_guid' => new RawExp(':membership_guid')
            ]);

        $statement = $query->prepare();
        $this->mysqlHandler->bindValuesToPreparedStatement($statement, [
            'tenant_id' => $tenantId,
            'permission_id' => $permissionId->name,
            'intent_type' => $intentType->value,
            'membership_guid' => $membershipGuid
        ]);

        return $statement->execute();
    }
}
