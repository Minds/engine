<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Repositories;

use Minds\Core\Data\MySQL\AbstractRepository;
use Minds\Core\MultiTenant\Enums\FeaturedEntityTypeEnum;
use Minds\Core\MultiTenant\Types\FeaturedEntity;
use Minds\Core\MultiTenant\Types\FeaturedGroup;
use Minds\Core\MultiTenant\Types\FeaturedUser;
use Minds\Exceptions\ServerErrorException;
use PDO;
use Selective\Database\Operator;
use Selective\Database\RawExp;

/**
 * Repository for featured entities.
 */
class FeaturedEntitiesRepository extends AbstractRepository
{
    /**
     * Gets featured entities for a tenant.
     * @param int $tenantId - The tenant ID.
     * @param FeaturedEntityTypeEnum $type - The type of entity to get.
     * @param int $limit - The limit of entities to get.
     * @param int|null $loadAfter - The load after cursor.
     * @param bool|null $hasMore - Whether there are more entities to load,
     * passed by reference and updated appropriately.
     * @return iterable<FeaturedUser|FeaturedGroup>
     */
    public function getFeaturedEntities(
        int $tenantId,
        FeaturedEntityTypeEnum $type,
        int $limit = 12,
        int $loadAfter = null,
        ?bool &$hasMore = null
    ): iterable {
        $query = $this->mysqlClientReaderHandler->select()
            ->from('minds_tenant_featured_entities')
            ->innerJoin(['entities' => 'minds_entities'], 'minds_tenant_featured_entities.entity_guid', Operator::EQ, 'entities.guid');

        if ($type === FeaturedEntityTypeEnum::GROUP) {
            $query->innerJoin(['groups' => 'minds_entities_group'], 'entities.guid', Operator::EQ, 'groups.guid');
        } else {
            $query->innerJoin(['users' => 'minds_entities_user'], 'entities.guid', Operator::EQ, 'users.guid');
        }

        $query->where('minds_tenant_featured_entities.tenant_id', Operator::EQ, new RawExp(':tenant_id'))
            ->where('type', Operator::EQ, new RawExp(':type'))
            ->where('auto_subscribe', Operator::EQ, true)
            ->orWhere('recommended', Operator::EQ, true)
            ->orderBy('updated_timestamp ASC')
            ->limit($limit);

        if ($loadAfter) {
            $query->offset($loadAfter);
        }

        $statement = $query->prepare();

        $this->mysqlHandler->bindValuesToPreparedStatement($statement, [
            'tenant_id' => $tenantId,
            'type' => $type->value
        ]);

        $statement->execute();
        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);

        $hasMore = count($rows) >= $limit;

        foreach ($rows as $row) {
            yield $this->buildFeaturedEntity($row);
        }
    }

    /**
     * Upserts a featured entity.
     * @param FeaturedEntity $featuredEntity - The featured entity to upsert.
     * @return FeaturedEntity - The upserted featured entity.
     */
    public function upsertFeaturedEntity(
        FeaturedEntity $featuredEntity
    ): FeaturedEntity {
        $boundValues = [
            'tenant_id' => $featuredEntity->tenantId,
            'entity_guid' => $featuredEntity->entityGuid,
            'updated_timestamp' => date('c', time()),
        ];
        $rawValues = [
            'updated_timestamp' => new RawExp(':updated_timestamp')
        ];

        if (isset($featuredEntity->autoSubscribe)) {
            $rawValues['auto_subscribe'] = new RawExp(':auto_subscribe');
            $boundValues['auto_subscribe'] = $featuredEntity->autoSubscribe;
        }

        if (isset($featuredEntity->recommended)) {
            $rawValues['recommended'] = new RawExp(':recommended');
            $boundValues['recommended'] = $featuredEntity->recommended;
        }

        $query = $this->mysqlClientWriterHandler
            ->insert()
            ->into('minds_tenant_featured_entities')
            ->set([
                'tenant_id' => new RawExp(':tenant_id'),
                'entity_guid' => new RawExp(':entity_guid'),
                ...$rawValues
            ])
            ->onDuplicateKeyUpdate($rawValues)
            ->prepare();

        $this->mysqlHandler->bindValuesToPreparedStatement($query, $boundValues);

        if (!$query->execute()) {
            throw new ServerErrorException('Failed to upsert featured entity');
        }

        return $featuredEntity;
    }

    /**
     * Deletes a featured entity.
     * @param integer $tenantId - The id of the tenant.
     * @param integer $entityGuid - The entityGuid.
     * @return bool - Whether the entity was deleted.
     */
    public function deleteFeaturedEntity(int $tenantId, int $entityGuid): bool
    {
        $query = $this->mysqlClientWriterHandler
            ->delete()
            ->from('minds_tenant_featured_entities')
            ->where('tenant_id', Operator::EQ, new RawExp(':tenant_id'))
            ->where('entity_guid', Operator::EQ, new RawExp(':entity_guid'))
            ->prepare();

        $this->mysqlHandler->bindValuesToPreparedStatement($query, [
            'tenant_id' => $tenantId,
            'entity_guid' => $entityGuid
        ]);

        return $query->execute();
    }

    /**
     * Builds a featured entity from a row.
     * @param array $row - The row to build from.
     * @return FeaturedUser|FeaturedGroup - The built featured entity.
     */
    private function buildFeaturedEntity(array $row): FeaturedUser|FeaturedGroup
    {
        return match($row['type']) {
            'user' => new FeaturedUser(
                tenantId: (int) $row['tenant_id'],
                entityGuid: (int) $row['entity_guid'],
                autoSubscribe: (bool) $row['auto_subscribe'],
                recommended: (bool) $row['recommended'],
                username: $row['username'],
                name: $row['name']
            ),
            'group' => new FeaturedGroup(
                tenantId: (int) $row['tenant_id'],
                entityGuid: (int) $row['entity_guid'],
                autoSubscribe: (bool) $row['auto_subscribe'],
                recommended: (bool) $row['recommended'],
                name: $row['name']
            ),
            default => null
        };
    }
}
