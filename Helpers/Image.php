<?php
namespace Minds\Helpers;

class Image
{
    /**
     * Get the size of an image
     * @param string
     */
    public function getimagesize($path) {
        return getimagesize($path);
    }
}