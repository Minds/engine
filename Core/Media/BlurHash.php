<?php
/**
 * Blurhash service. Given a stringified image, returns a small hash that could be decoded to represent a blurry image
 */

namespace Minds\Core\Media;

use Minds\Core\Di\Di;

class BlurHash
{
    /**
     * @param string $imagePath the image
     * @return string the hash or null on error
     */
    public function getHash(string $imagePath): string
    {
        $components_x = 4;
        $components_y = 3;
        return bh_encode($components_x, $components_y, $imagePath);
    }
}
