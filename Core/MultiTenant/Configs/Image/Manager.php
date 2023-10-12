<?php

declare(strict_types=1);

namespace Minds\Core\MultiTenant\Configs\Image;

use Minds\Core\Media\Imagick\Manager as ImagickManager;
use Minds\Core\MultiTenant\Configs\Enums\MultiTenantConfigImageType;
use Minds\Entities\File;

class Manager
{
    public function __construct(
        private ImagickManager $imagickManager
    ) {
    }

    public function upload(string $fileName, MultiTenantConfigImageType $imageType): void {
        $imageFile = $this->imagickManager->setImage($fileName)
            ->getJpeg();

        $file = new File();
        $file->setFilename("config/{$imageType->value}.jpg");
        $file->open('write');
        $file->write($imageFile);
        $file->close();
    }
}
