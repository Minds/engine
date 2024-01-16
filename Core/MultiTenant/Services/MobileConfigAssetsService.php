<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Services;

use ElggFile;
use Exception;
use ImagickException;
use InvalidParameterException;
use IOException;
use Minds\Core\Config\Config;
use Minds\Core\Media\Imagick\Manager as ImagickManager;
use Minds\Core\MultiTenant\Configs\Enums\MultiTenantConfigImageType;
use Minds\Core\MultiTenant\Configs\Manager as MultiTenantConfigManager;
use Minds\Core\MultiTenant\Enums\MobileConfigImageTypeEnum;
use Minds\Entities\File;

class MobileConfigAssetsService
{
    public function __construct(
        private readonly ImagickManager           $imagickManager,
        private readonly Config                   $config,
        private readonly MultiTenantBootService   $multiTenantBootService,
        private readonly MultiTenantConfigManager $multiTenantConfigManager
    ) {
    }

    /**
     * Upload an image for the passed in type.
     * @param MobileConfigImageTypeEnum $imageType - type of image being uploaded.
     * @param string $filename
     * @return void
     * @throws IOException
     * @throws ImagickException
     * @throws InvalidParameterException
     */
    public function upload(MobileConfigImageTypeEnum $imageType, string $filename): void
    {
        $file = new File();
        $file->setFilename("config/{$imageType->value}.png");
        $file->owner_guid = $this->getTenantOwnerGuid();
        $file->open('write');
        $file->write(
            $this->imagickManager->setImage($filename)
                ->getPng()
        );
        $file->close();

        $this->multiTenantConfigManager->upsertConfigs(
            lastCacheTimestamp: time()
        );
    }

    /**
     * Gets the owner guid for the tenant.
     * @return int - owner guid of the tenant.
     */
    public function getTenantOwnerGuid(): int
    {
        return $this->multiTenantBootService->getTenant()->rootUserGuid;
    }

    /**
     * Gets the appropriate image for the passed in type.
     * @param MultiTenantConfigImageType $type - type of image being retrieved.
     * @return ElggFile - file object.
     */
    public function getImageFileByType(MultiTenantConfigImageType $type): ElggFile
    {
        $file = new ElggFile();
        $file->setFilename("config/{$type->value}.png");
        $file->owner_guid = $this->getTenantOwnerGuid();
        return $file;
    }

    /**
     * Gets the contents of the passed in file - if one is not found will return a default image.
     * @param ElggFile $file - file object.
     * @return mixed - contents of the file.
     * @throws Exception
     */
    public function getImageContentsFromFile(ElggFile $file, MultiTenantConfigImageType $type): mixed
    {
        $contents = $file->read();
        if (empty($contents)) {
            $fileName = match ($type) {
                MobileConfigImageTypeEnum::SQUARE_LOGO => 'default-square-logo.png',
                MobileConfigImageTypeEnum::HORIZONTAL_LOGO => 'default-horizontal-logo.png',
                default => throw new Exception('Unexpected match value'),
            };
            $filepath = $this->config->get('path') . "engine/Assets/tenant/$fileName";
            $contents = file_get_contents($filepath);
        }
        return $contents;
    }
}
