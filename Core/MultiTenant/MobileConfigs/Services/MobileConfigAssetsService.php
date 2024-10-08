<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\MobileConfigs\Services;

use ElggFile;
use ImagickException;
use InvalidParameterException;
use IOException;
use Minds\Core\Config\Config;
use Minds\Core\Media\Imagick\Manager as ImagickManager;
use Minds\Core\MultiTenant\Configs\Enums\MultiTenantConfigImageType;
use Minds\Core\MultiTenant\Configs\Manager as MultiTenantConfigManager;
use Minds\Core\MultiTenant\MobileConfigs\Enums\MobileConfigImageTypeEnum;
use Minds\Core\MultiTenant\Services\MultiTenantBootService;
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
        $file->setFilename("mobile_config/{$imageType->value}.png");
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
     * Upload an image blob for the specified mobile config image type.
     * @param string $imageBlob - The image data as a binary string.
     * @param MobileConfigImageTypeEnum $imageType - The type of mobile config image being uploaded.
     * @return bool
     */
    public function uploadBlob(string $imageBlob, MobileConfigImageTypeEnum $imageType): bool
    {
        $file = new File();
        $file->setFilename("mobile_config/{$imageType->value}.png");
        $file->owner_guid = $this->getTenantOwnerGuid();
        $file->open('write');
        $success = (bool) $file->write($imageBlob);
        $file->close();

        $this->multiTenantConfigManager->upsertConfigs(
            lastCacheTimestamp: time()
        );

        return $success;
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
    public function getImageFileByType(MobileConfigImageTypeEnum $type): ElggFile
    {
        $file = new ElggFile();
        $file->setFilename("mobile_config/{$type->value}.png");
        $file->owner_guid = $this->getTenantOwnerGuid();
        return $file;
    }

    /**
     * Gets the contents of the passed in file - if one is not found will return a default image.
     * @param ElggFile $file - file object.
     * @return mixed - contents of the file.
     */
    public function getImageContentsFromFile(MobileConfigImageTypeEnum $type, ElggFile $file): mixed
    {
        $contents = $file->read();
        if (empty($contents)) {
            $fileName = match ($type) {
                MobileConfigImageTypeEnum::ICON => 'default-square-logo.png',
                MobileConfigImageTypeEnum::SPLASH => 'default-square-logo.png',
                MobileConfigImageTypeEnum::SQUARE_LOGO => 'default-square-logo.png',
                MobileConfigImageTypeEnum::HORIZONTAL_LOGO => 'default-horizontal-logo.png',
                MobileConfigImageTypeEnum::MONOGRAPHIC_ICON => 'default-monographic-icon.png',
            };
            $filepath = $this->config->get('path') . "engine/Assets/tenant/$fileName";
            $contents = file_get_contents($filepath);
        }
        return $contents;
    }
}
