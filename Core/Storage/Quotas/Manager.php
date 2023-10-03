<?php
declare(strict_types=1);

namespace Minds\Core\Storage\Quotas;

use Exception;
use Minds\Core\Config\Config;
use Minds\Core\Log\Logger;
use Minds\Core\Storage\Quotas\Enums\AssetTypeEnum;
use Minds\Core\Storage\Quotas\Repositories\MySqlRepository;
use Minds\Core\Storage\Services\ServiceInterface;
use Minds\Entities\Image;
use Minds\Entities\Video;
use NotImplementedException;

class Manager
{
    public function __construct(
        private readonly ServiceInterface $storage,
        private readonly MySqlRepository $mysqlRepository,
        private readonly Config $config,
        private readonly Logger $logger
    ) {
    }

    /**
     * @param Video|Image $asset
     * @return void
     * @throws Exception
     */
    public function storeAssetQuota(Video|Image $asset): void
    {
        match (get_class($asset)) {
            Video::class => $this->storeVideoQuota($asset),
            Image::class => $this->storeImageQuota($asset),
            default => throw new Exception('Invalid asset type')
        };
    }

    private function storeVideoQuota(Video $asset): void
    {
        $key = $this->config->get('transcoder')['dir'] . "/" . $asset->get('cinemr_guid') . "/source";

        $assetStats = $this->storage
            ->open($key, 'read')
            ->stats();

        $this->mysqlRepository->storeAsset(
            (int) $asset->getOwnerGuid(),
            (int) $asset->getGuid(),
            AssetTypeEnum::VIDEO,
            $assetStats['ObjectSize'],
            false
        );
    }

    /**
     * @param Image $asset
     * @return void
     * @throws NotImplementedException
     */
    private function storeImageQuota(Image $asset): void
    {
        throw new NotImplementedException();
    }

    public function flagAssetAsDeleted(Video|Image $asset): void
    {
        $this->mysqlRepository->markAssetAsDeleted((int) $asset->getGuid());
    }
}
