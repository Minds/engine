<?php
declare(strict_types=1);

namespace Minds\Core\Storage\Quotas\Types;

use Minds\Core\Storage\Quotas\Enums\AssetTypeEnum;

class AssetMetadata
{
    public function __construct(
        public int $ownerGuid,
        public int $entityGuid,
        public string $filename,
        public AssetTypeEnum $assetType,
        public int $size,
        public int $createdTimestamp,
        public ?int $updatedTimestamp = null,
        public bool $deleted = false,
    ) {
    }
}
