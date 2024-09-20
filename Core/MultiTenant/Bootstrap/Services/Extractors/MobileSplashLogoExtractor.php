<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Bootstrap\Services\Extractors;

use Minds\Core\MultiTenant\Bootstrap\Services\Processors\LogoImageProcessor;

/**
 * Extracts a mobile splash logo from a square logo.
 */
class MobileSplashLogoExtractor
{
    public function __construct(
        private LogoImageProcessor $logoImageProcessor
    ) {
    }

    /**
     * Extracts a mobile splash logo from a square logo.
     * @param string $squareLogoBlob - The blob of the square logo.
     * @return string|null - The blob of the mobile splash logo.
     */
    public function extract(string $squareLogoBlob): ?string
    {
        $image = new \Imagick();
        $image->readImageBlob($squareLogoBlob);
        $image = $this->logoImageProcessor->toPng($image);

        $logoAspectRatio = $image->getImageWidth() / $image->getImageHeight();
        $horizontal = $logoAspectRatio > 1;

        $image = $horizontal ?
            $this->logoImageProcessor->addPadding($image, $logoAspectRatio * 1.6) :
            $this->logoImageProcessor->addPadding($image, 3.66);

        $blob = $image->getImageBlob();

        $image?->clear();
        $image?->destroy();

        return $blob;
    }
}
