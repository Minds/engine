<?php
declare(strict_types=1);

namespace Minds\Core\Storage\Quotas\Repositories;

use Minds\Core\Data\MySQL\AbstractRepository;
use Minds\Core\Data\MySQL\Client;
use Minds\Core\Log\Logger;
use Minds\Core\Storage\Quotas\Enums\AssetTypeEnum;
use Selective\Database\Operator;

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
     * @param AssetTypeEnum $assetTypeEnum
     * @param int $assetSizeInBytes
     * @param bool $deleted
     * @return bool
     */
    public function storeAsset(
        int $assetOwnerGuid,
        int $assetGuid,
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
                'entity_type' => $assetTypeEnum->value,
                'size' => $assetSizeInBytes,
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
}
