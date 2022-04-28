<?php
/**
 * Blurhash service. Given a stringified image, returns a small hash that could be decoded to represent a blurry image
 */

namespace Minds\Core\Media;

use Minds\Core\Di\Di;

class BlurHash
{
    /**
     * @param string $image the image (we receive the string as a references because it is a blob and we don't want to copy it)
     * @return string the hash or null on error
     */
    public function getHash(string &$image): string
    {
        $components_x = 4;
        $components_y = 3;
        return bh_encode_blob($components_x, $components_y, $image);
    }
}
