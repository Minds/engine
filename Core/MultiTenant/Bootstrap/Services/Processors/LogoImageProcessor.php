<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Bootstrap\Services\Processors;

/**
 * Processes logo images.
 */
class LogoImageProcessor
{
    /**
     * Converts an image to PNG format.
     * @param \Imagick $image - The input image.
     * @return \Imagick The converted image.
     */
    public function toPng(\Imagick $image): \Imagick
    {
        $initialFormat = $image->getImageFormat();

        switch ($initialFormat) {
            case 'SVG':
                return $this->convertSvgToPng($image->getImageBlob());
            case 'JPEG':
                $image->setImageFormat('png');
                return $image;
            default:
                return $image;
        }
    }

    /**
     * Removes empty space around a logo.
     * @param \Imagick $image - The input image.
     * @return \Imagick The trimmed image.
     */
    public function trim(\Imagick $image): \Imagick
    {
        $image->trimImage(0);
        $image->setImagePage(0, 0, 0, 0);
        return $image;
    }

    /**
     * Adds padding to a logo.
     * @param \Imagick $image - The input image.
     * @param float $ratio - The goal aspect ratio to pad to.
     * @return \Imagick The padded image.
     */
    public function addPadding(\Imagick $image, float $ratio): \Imagick
    {
        $originalWidth = $image->getImageWidth();
        $originalHeight = $image->getImageHeight();
        $originalRatio = $originalWidth / $originalHeight;

        if ($originalRatio > $ratio) {
            // Image is wider than target ratio, pad vertically.
            $canvasWidth = $originalWidth;
            $canvasHeight = (int) ($originalWidth / $ratio);
        } else {
            // Image is taller than or equal to target ratio, pad horizontally.
            $canvasHeight = $originalHeight;
            $canvasWidth = (int) ($originalHeight * $ratio);
        }

        $canvas = new \Imagick();
        $canvas->newImage($canvasWidth, $canvasHeight, new \ImagickPixel('transparent'));
        $canvas->setImageFormat('png');

        // Calculate position to center the logo.
        $x = ($canvasWidth - $image->getImageWidth()) / 2;
        $y = ($canvasHeight - $image->getImageHeight()) / 2;

        // Composite the logo onto the canvas.
        $canvas->compositeImage($image, \Imagick::COMPOSITE_OVER, (int) $x, (int) $y);

        $image?->clear();
        $image?->destroy();

        // Get the new image data.
        return $canvas;
    }

    /**
     * Converts an SVG blob to a PNG blob.
     * @param string $svgBlob - The SVG blob to convert.
     * @return \Imagick|null - The PNG blob or null if conversion fails.
     */
    private function convertSvgToPng(string $svgBlob): \Imagick
    {
        try {
            $image = new \Imagick();
            $image->setBackgroundColor(new \ImagickPixel('transparent'));
            $image->readImageBlob($svgBlob);
            $image->setImageFormat("png32");
            return $image;
        } catch (\Exception $e) {
            return null;
        }
    }
}
