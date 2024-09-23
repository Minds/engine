<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Bootstrap\Services\Extractors;

use Minds\Core\Log\Logger;
use Minds\Core\MultiTenant\Bootstrap\Services\Processors\LogoImageProcessor;

/**
 * Extracts a horizontal logo from a square logo.
 */
class HorizontalLogoExtractor
{
    public function __construct(
        private LogoImageProcessor $logoImageProcessor,
        private Logger $logger
    ) {
    }

    /**
     * Extracts a horizontal logo from a square logo.
     * @param string $squareLogoBlob - The blob of the square logo.
     * @return string|null - The blob of the horizontal logo.
     */
    public function extract(string $squareLogoBlob): ?string
    {
        $image = new \Imagick();

        try {
            $image->readImageBlob($squareLogoBlob);
            $image = $this->logoImageProcessor->toPng($image);
            $image = $this->logoImageProcessor->trim($image);
            $image = $this->logoImageProcessor->addPadding($image, 3.18);
            $blob = $image->getImageBlob();
            return $blob;
        } catch (\Exception $e) {
            $this->logger->error($e);
            return null;
        } finally {
            $image?->clear();
            $image?->destroy();
        }
    }
}
