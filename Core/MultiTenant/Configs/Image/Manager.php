<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Configs\Image;

use ElggFile;
use Minds\Core\Config\Config;
use Minds\Core\Media\Imagick\Manager as ImagickManager;
use Minds\Core\MultiTenant\Configs\Enums\MultiTenantConfigImageType;
use Minds\Core\MultiTenant\Configs\Manager as MultiTenantConfigManager;
use Minds\Core\MultiTenant\Services\MultiTenantBootService;
use Minds\Entities\File;

/**
 * Manager handling the retrieval and upload of multi-tenant config images.
 */
class Manager
{
    public function __construct(
        private ImagickManager $imagickManager,
        private Config $config,
        private MultiTenantBootService $multiTenantBootService,
        private MultiTenantConfigManager $multiTenantConfigManager
    ) {
    }

    /**
     * Upload an image for the passed in type.
     * @param string $fileName - tmp_name of the file to upload.
     * @param MultiTenantConfigImageType $imageType - type of image being uploaded.
     * @return void
     */
    public function upload(string $fileName, MultiTenantConfigImageType $imageType): void
    {
        $imageFile = $this->imagickManager->setImage($fileName)
            ->getPng();

        $file = new File();
        $file->setFilename("config/{$imageType->value}.png");
        $file->owner_guid = $this->getTenantOwnerGuid();
        $file->open('write');
        $file->write($imageFile);
        $file->close();

        $this->multiTenantConfigManager->upsertConfigs(
            lastCacheTimestamp: time()
        );
    }

    /**
     * Upload an image blob for the passed in type.
     * @param string $imageBlob - binary image data to upload.
     * @param MultiTenantConfigImageType $imageType - type of image being uploaded.
     * @return void
     */
    public function uploadBlob(string $imageBlob, MultiTenantConfigImageType $imageType): void
    {
        $file = new File();
        $file->setFilename("config/{$imageType->value}.png");
        $file->owner_guid = $this->getTenantOwnerGuid();
        $file->open('write');
        $file->write($imageBlob);
        $file->close();

        $this->multiTenantConfigManager->upsertConfigs(
            lastCacheTimestamp: time()
        );
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
     */
    public function getImageContentsFromFile(ElggFile $file, MultiTenantConfigImageType $type): mixed
    {
        $contents = $file->read();
        if (empty($contents)) {
            $fileName = match($type) {
                MultiTenantConfigImageType::FAVICON => 'default-favicon.png',
                MultiTenantConfigImageType::SQUARE_LOGO => 'default-square-logo.png',
                MultiTenantConfigImageType::HORIZONTAL_LOGO => 'default-horizontal-logo.png',
                MultiTenantConfigImageType::MONOGRAPHIC_ICON => 'default-monographic-icon.png',
            };
            $filepath = $this->config->get('path') . "engine/Assets/tenant/$fileName";
            $contents = file_get_contents($filepath);
        }
        return $contents;
    }

    /**
     * Gets the owner guid for the tenant.
     * @return int - owner guid of the tenant.
     */
    public function getTenantOwnerGuid(): int
    {
        return $this->multiTenantBootService->getTenant()->rootUserGuid;
    }
}
