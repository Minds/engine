<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Configs\Image;

use ElggFile;
use Minds\Core\Config\Config;
use Minds\Core\Media\Imagick\Manager as ImagickManager;
use Minds\Core\MultiTenant\Configs\Enums\MultiTenantConfigImageType;
use Minds\Entities\File;

class Manager
{
    public function __construct(
        private ImagickManager $imagickManager,
        private Config $config
    ) {
    }

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
    }

    public function getImageFileByType(MultiTenantConfigImageType $type): ElggFile
    {
        $file = new ElggFile();
        $file->setFilename("config/{$type->value}.png");
        $file->owner_guid = $this->getTenantOwnerGuid();
        return $file;
    }

    public function getImageContentsFromFile(ElggFile $file): mixed
    {
        $contents = isset($file) ? $file->read() : null;
        if (empty($contents)) {
            $filepath = $this->config->get('path') . "engine/Assets/avatars/default-master.png";
            $contents = file_get_contents($filepath);
        }
        return $contents;
    }

    private function getTenantOwnerGuid(): int
    {
        return ((int) $this->config->get('tenant_owner_guid')) ?? 0;
    }
}
