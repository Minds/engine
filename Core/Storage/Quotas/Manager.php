<?php
declare(strict_types=1);

namespace Minds\Core\Storage\Quotas;

use Exception;
use Minds\Core\Config\Config;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Feeds\GraphQL\Types\ActivityEdge;
use Minds\Core\GraphQL\Types\PageInfo;
use Minds\Core\Log\Logger;
use Minds\Core\Storage\Quotas\Enums\AssetTypeEnum;
use Minds\Core\Storage\Quotas\Enums\VideoAssetQuality;
use Minds\Core\Storage\Quotas\Enums\VideoAssetQualityEnum;
use Minds\Core\Storage\Quotas\Enums\VideoQualityEnum;
use Minds\Core\Storage\Quotas\Repositories\MySqlRepository;
use Minds\Core\Storage\Quotas\Types\AssetConnection;
use Minds\Core\Storage\Quotas\Types\AssetMetadata;
use Minds\Core\Storage\Quotas\Types\QuotaDetails;
use Minds\Entities\Activity;
use Minds\Entities\Image;
use Minds\Entities\Video;
use Minds\Exceptions\ServerErrorException;
use NotImplementedException;
use Oracle\Oci\ObjectStorage\ObjectStorageClient;

class Manager
{
    public function __construct(
        private readonly ObjectStorageClient $ociObjectStorageClient,
        private readonly MySqlRepository $mysqlRepository,
        private readonly EntitiesBuilder $entitiesBuilder,
        private readonly Config $config,
        private readonly Logger $logger
    ) {
    }

    /**
     * @param Video|Image $asset
     * @return void
     * @throws Exception
     */
    public function storeAssetQuota(
        Video|Image $asset,
        string|false $filename = false
    ): void {
        match (get_class($asset)) {
            Video::class => $this->storeVideoQuota($asset, $filename),
            Image::class => $this->storeImageQuota($asset),
            default => throw new Exception('Invalid asset type')
        };
    }

    public function storeVideoDuration(
        Video $asset,
        float $durationInSeconds,
        ?int $tenantId
    ): void {
        $this->mysqlRepository->storeVideoDuration(
            (int) $asset->getGuid(),
            $durationInSeconds,
            $tenantId
        );
    }

    /**
     * @param Video $asset
     * @param string|false $filename
     * @return void
     * @throws Exception
     */
    private function storeVideoQuota(Video $asset, string|false $filename): void
    {
        if (!$filename) {
            $filename = $this->config->get('transcoder')['dir'] . "/" . $asset->get('cinemr_guid') . "/source";
        }

        $videoQuality = VideoQualityEnum::fromFilenameSuffix($filename);

        $assetSize = $this->getAssetSize($asset, $filename);

        $this->mysqlRepository->storeAsset(
            (int) $asset->getOwnerGuid(),
            (int) $asset->getGuid(),
            ($this->config->get('tenant_id')) ? (int) $this->config->get('tenant_id') : null,
            $videoQuality->value,
            AssetTypeEnum::VIDEO,
            $assetSize,
            false
        );
    }

    /**
     * @param Video|Image $asset
     * @param string|false $key
     * @return int
     * @throws Exception
     */
    private function getAssetSize(Video|Image $asset, string|false $key): int
    {
        if (!$key) {
            $key = $this->config->get('transcoder')['dir'] . "/" . $asset->get('cinemr_guid') . "/source";
        }

        $response = $this->ociObjectStorageClient
            ->headObject([
                'namespaceName' => $this->config->get('oci')['api_auth']['bucket_namespace'],
                'bucketName' => $this->config->get('transcoder')['oci_bucket_name'],
                'objectName' => $key
            ]);

        if ($response->getStatusCode() !== 200) {
            throw new Exception('Failed to get asset size');
        }

        return (int) $response->getHeaders()['Content-Length'][0];
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

    /**
     * @param int $userId
     * @return QuotaDetails
     * @throws ServerErrorException
     */
    public function getUserQuotaUsage(int $userId): QuotaDetails
    {
        $quotaUsage = $this->mysqlRepository->getUserQuotaUsage($userId);

        return new QuotaDetails(
            $quotaUsage
        );
    }

    /**
     * @param int $userId
     * @param int $first
     * @param string|null $cursor
     * @return AssetConnection
     * @throws ServerErrorException
     */
    public function getUserAssets(
        int $userId,
        int $first = 12,
        ?string $cursor = null
    ): AssetConnection {
        if (!$cursor) {
            $limit = $first;
            $offset = 0;
        } else {
            ['limit' => $limit, 'offset' => $offset] = json_decode(base64_decode($cursor, true), true);
        }

        $assetConnection = new AssetConnection();
        $hasNextPage = false;
        $edges = [];
        foreach (
            $this->mysqlRepository->getUserAssetsMetadata(
                userId: $userId,
                limit: $limit,
                offset: $offset,
                hasNextPage: $hasNextPage
            ) as $assetMetadata
        ) {
            try {
                $edges[] = $this->processAssetsEdge($assetMetadata);
            } catch (Exception $e) {
                $this->logger->error($e->getMessage());
                continue;
            }
        }

        $assetConnection->setEdges($edges);
        $assetConnection->setPageInfo(new PageInfo(
            hasNextPage: $hasNextPage,
            hasPreviousPage: $offset > 0,
            startCursor: base64_encode(json_encode(['limit' => $limit, 'offset' => $offset])),
            endCursor: base64_encode(json_encode(['limit' => $limit, 'offset' => $limit + $offset])),
        ));

        return $assetConnection;
    }

    /**
     * @return QuotaDetails
     * @throws ServerErrorException
     */
    public function getTenantQuotaUsage(): QuotaDetails
    {
        $quotaUsage = $this->mysqlRepository->getTenantQuotaUsage(((int) $this->config->get('tenant_id')) ?? null);

        return new QuotaDetails(
            $quotaUsage
        );
    }

    /**
     * @param int $first
     * @param string|null $cursor
     * @return AssetConnection
     * @throws ServerErrorException
     */
    public function getTenantAssets(
        int $first = 12,
        ?string $cursor = null
    ): AssetConnection {
        if (!$cursor) {
            $limit = $first;
            $offset = 0;
        } else {
            ['limit' => $limit, 'offset' => $offset] = json_decode(base64_decode($cursor, true), true);
        }

        $assetConnection = new AssetConnection();
        $hasNextPage = false;
        $edges = [];
        foreach (
            $this->mysqlRepository->getTenantAssetsMetadata(
                tenantId: ($this->config->get('tenant_id')) ? (int) $this->config->get('tenant_id') : null,
                limit: $limit,
                offset: $offset,
                hasNextPage: $hasNextPage
            ) as $assetMetadata
        ) {
            try {
                $edges[] = $this->processAssetsEdge($assetMetadata);
            } catch (Exception $e) {
                $this->logger->error($e->getMessage());
                continue;
            }
        }

        $assetConnection->setEdges($edges);
        $assetConnection->setPageInfo(new PageInfo(
            hasNextPage: $hasNextPage,
            hasPreviousPage: $offset > 0,
            startCursor: base64_encode(json_encode(['limit' => $limit, 'offset' => $offset])),
            endCursor: base64_encode(json_encode(['limit' => $limit, 'offset' => $limit + $offset])),
        ));

        return $assetConnection;
    }

    /**
     * @param AssetMetadata $assetMetadata
     * @return ActivityEdge
     * @throws Exception
     */
    public function processAssetsEdge(AssetMetadata $assetMetadata): ActivityEdge
    {
        /**
         * @var Video|Image $asset
         */
        $asset = $this->entitiesBuilder->single($assetMetadata->entityGuid);
        if (!$asset) {
            throw new Exception("Asset $assetMetadata->entityGuid not found");
        }

        /**
         * @var Activity $activity
         */
        $activity = $this->entitiesBuilder->single($asset->getContainerGUID());
        if (!$activity) {
            throw new Exception("Activity {$asset->getContainerGUID()} not found");
        }

        if (!($activity instanceof Activity)) {
            // TODO: possibly an uncompleted activity post. Consider deleting the entry now.
            throw new Exception("Entity {$asset->getContainerGUID()} is not an activity");
        }

        return new ActivityEdge(
            activity: $activity,
            cursor: base64_encode($activity->getGuid()),
            explicitVotes: false
        );
    }
}
