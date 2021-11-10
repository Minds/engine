<?php
/**
 * Blurhash service. Given a stringified image, returns a small hash that could be decoded to represent a blurry image
 */

namespace Minds\Core\Media;

use Minds\Core\Di\Di;
use kornrunner\Blurhash\Blurhash as PhpBlurHash;

class BlurHash
{
    public function __construct(
        $config = null
    ) {
        $this->config = $config ?: Di::_()->get('Config');
    }

    /**
     * @param string $imageBlob the stringified image
     * @return string the hash
     */
    public function getHash(string $imageBlob): string
    {
        $image = imagecreatefromstring($imageBlob);
        $width = imagesx($image);
        $height = imagesy($image);

        // // resize the image to save processing if larger than $max_width:
        // $max_width = 20;
        // if ($width > $max_width) {
        //     $image = imagescale($image, $max_width);
        //     $width = imagesx($image);
        //     $height = imagesy($image);
        // }

        $pixels = [];
        for ($y = 0; $y < $height; ++$y) {
            $row = [];
            for ($x = 0; $x < $width; ++$x) {
                $index = imagecolorat($image, $x, $y);
                $colors = imagecolorsforindex($image, $index);

                $row[] = [$colors['red'], $colors['green'], $colors['blue']];
            }
            $pixels[] = $row;
        }

        $components_x = 4;
        $components_y = 3;
        $blurhash = PhpBlurHash::encode($pixels, $components_x, $components_y);

        return $blurhash;
    }
}
