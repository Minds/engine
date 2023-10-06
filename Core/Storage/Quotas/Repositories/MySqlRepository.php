<?php
declare(strict_types=1);

namespace Minds\Core\Storage\Quotas\Repositories;

use Minds\Core\Data\MySQL\AbstractRepository;
use Minds\Core\Data\MySQL\Client;
use Minds\Core\Log\Logger;
use Minds\Core\Storage\Quotas\Enums\AssetTypeEnum;
use Minds\Core\Storage\Quotas\Types\AssetMetadata;
use Minds\Exceptions\ServerErrorException;
use PDO;
use Selective\Database\Operator;
use Selective\Database\RawExp;
use Selective\Database\SelectQuery;

class MySqlRepository extends AbstractRepository
{
    private const DB_TABLE = "minds_asset_storage";

    public function __construct(
        protected Client $mysqlHandler,
        protected Logger $logger
    ) {
        parent::__construct($mysqlHandler, $logger);
    }

    /**
     * @param int $assetOwnerGuid
     * @param int $assetGuid
     * @param int $tenantId
     * @param string $filename
     * @param AssetTypeEnum $assetTypeEnum
     * @param int $assetSizeInBytes
     * @param bool $deleted
     * @return bool
     */
    public function storeAsset(
        int $assetOwnerGuid,
        int $assetGuid,
        int $tenantId,
        string $filename,
        AssetTypeEnum $assetTypeEnum,
        int $assetSizeInBytes,
        bool $deleted = false
    ): bool {
        return $this->mysqlClientWriterHandler
            ->insert()
            ->into(self::DB_TABLE)
            ->set([
                'owner_guid' => $assetOwnerGuid,
                'entity_guid' => $assetGuid,
                'tenant_id' => $tenantId,
                'entity_type' => $assetTypeEnum->value,
                'filename' => $filename,
                'size_bytes' => $assetSizeInBytes,
            ])
            ->onDuplicateKeyUpdate([
                // TODO: should we also update the file size?
                'deleted' => $deleted,
            ])
            ->execute();
    }

    /**
     * @param int $assetGuid
     * @return bool
     */
    public function markAssetAsDeleted(
        int $assetGuid
    ): bool {
        return $this->mysqlClientWriterHandler
            ->update()
            ->table(self::DB_TABLE)
            ->set([
                'deleted' => true,
            ])
            ->where('entity_guid', Operator::EQ, $assetGuid)
            ->execute();
    }

    /**
     * @param int $userId
     * @return int The total storage usage for the tenant, in bytes
     * @throws ServerErrorException
     */
    public function getUserQuotaUsage(int $userId): int
    {
        $stmt = $this->getBaseQuotaUsageQuery()
            ->where('owner_guid', Operator::EQ, $userId)
            ->prepare();
        if (!$stmt->execute()) {
            throw new ServerErrorException('Failed to get tenant quota usage');
        }

        return (int) $stmt->fetch(PDO::FETCH_ASSOC)['total_usage'] ?? 0;
    }

    /**
     * @param int $userId
     * @param AssetTypeEnum|false $assetTypeEnum
     * @param int $limit
     * @param int $offset
     * @param bool $hasNextPage
     * @return iterable
     * @throws ServerErrorException
     */
    public function getUserAssetsMetadata(
        int $userId,
        AssetTypeEnum|false $assetTypeEnum = false,
        int $limit = 12,
        int $offset = 0,
        bool &$hasNextPage = false,
    ): iterable {
        $stmt = $this->getBaseAssetsMetadataQuery($assetTypeEnum)
            ->where('owner_guid', Operator::EQ, $userId)
            ->orderBy('created_timestamp DESC')
            ->limit($limit + 1)
            ->offset($offset);

        return $this->yieldAssetsMetadata($stmt, $limit, $hasNextPage);
    }

    /**
     * @param int $tenantId
     * @return int The total storage usage for the tenant, in bytes
     * @throws ServerErrorException
     */
    public function getTenantQuotaUsage(int $tenantId): int
    {
        $stmt = $this->getBaseQuotaUsageQuery()
            ->where('tenant_id', Operator::EQ, $tenantId)
            ->prepare();
        if (!$stmt->execute()) {
            throw new ServerErrorException('Failed to get tenant quota usage');
        }

        return (int) $stmt->fetch(PDO::FETCH_ASSOC)['total_usage'] ?? 0;
    }

    /**
     * @param int $tenantId
     * @param AssetTypeEnum|false $assetTypeEnum
     * @param int $limit
     * @param int $offset
     * @param bool $hasNextPage
     * @return iterable
     * @throws ServerErrorException
     */
    public function getTenantAssetsMetadata(
        int $tenantId,
        AssetTypeEnum|false $assetTypeEnum = false,
        int $limit = 12,
        int $offset = 0,
        bool &$hasNextPage = false,
    ): iterable {
        $stmt = $this->getBaseAssetsMetadataQuery($assetTypeEnum)
            ->where('tenant_id', Operator::EQ, $tenantId)
            ->orderBy('created_timestamp DESC')
            ->limit($limit + 1)
            ->offset($offset);

        return $this->yieldAssetsMetadata($stmt, $limit, $hasNextPage);
    }

    /**
     * @param SelectQuery $stmt
     * @param int $limit
     * @param bool $hasNextPage
     * @return iterable
     * @throws ServerErrorException
     */
    private function yieldAssetsMetadata(
        SelectQuery $stmt,
        int $limit,
        bool &$hasNextPage
    ): iterable {
        $stmt = $stmt->prepare();

        if (!$stmt->execute()) {
            throw new ServerErrorException('Failed to get tenant assets metadata');
        }

        $i = 0;
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $asset) {
            yield new AssetMetadata(
                ownerGuid: $asset['owner_guid'],
                entityGuid: $asset['entity_guid'],
                filename: $asset['filename'],
                assetType: AssetTypeEnum::from($asset['entity_type']),
                size: (int) $asset['size_bytes'],
                createdTimestamp: (int) $asset['created_timestamp'],
                updatedTimestamp: $asset['updated_timestamp'] ?? null,
            );

            if ($i++ === $limit) {
                $hasNextPage = true;
            }
        }
    }

    /**
     * @return SelectQuery
     */
    private function getBaseQuotaUsageQuery(): SelectQuery
    {
        return $this->mysqlClientReaderHandler
            ->select()
            ->from(self::DB_TABLE)
            ->columns([
                new RawExp('SUM(size_bytes) as total_usage'),
            ])
            ->where('deleted', Operator::EQ, false);
    }

    /**
     * @param AssetTypeEnum|false $assetTypeEnum
     * @return SelectQuery
     */
    private function getBaseAssetsMetadataQuery(AssetTypeEnum|false $assetTypeEnum): SelectQuery
    {
        $stmt = $this->mysqlClientReaderHandler
            ->select()
            ->from(self::DB_TABLE)
            ->columns([
                'owner_guid',
                'entity_guid',
                'entity_type',
                'filename',
                'size_bytes',
                'created_timestamp',
                'updated_timestamp'
            ])
            ->where('deleted', Operator::EQ, false);

        if ($assetTypeEnum) {
            $stmt->where('entity_type', Operator::EQ, $assetTypeEnum->value);
        }

        return $stmt;
    }
}
