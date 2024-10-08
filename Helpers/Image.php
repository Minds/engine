<?php
namespace Minds\Helpers;

use Minds\Core\Di\Di;
use Minds\Core\Log\Logger;

class Image
{
    public function __construct(
        private ?Logger $logger = null
    ) {
        $this->logger ??= Di::_()->get('Logger');
    }

    /**
     * Get the size of an image
     * @param string
     */
    public function getimagesize($path)
    {
        return getimagesize($path);
    }

    /**
     * Validates whether the given blob is a valid image.
     * @param string $imageBlob - The image blob to validate.
     * @return bool - True if the blob is a valid image.
     */
    public function isValidImage(string $imageBlob): bool
    {
        try {
            $image = imagecreatefromstring($imageBlob);
            if ($image === false) {
                return false;
            }
            imagedestroy($image);
            return true;
        } catch (\Exception $e) {
            $this->logger->error("Error validating image: " . $e->getMessage());
            return false;
        }
    }
}
