<?php
namespace Minds\Core\Media\Imagick;

abstract class AbstractImagick
{
    public function __construct()
    {
        \Imagick::setResourceLimit(\Imagick::RESOURCETYPE_MEMORY, 268435456);
        \Imagick::setResourceLimit(\Imagick::RESOURCETYPE_MAP, 268435456);
    }
}
