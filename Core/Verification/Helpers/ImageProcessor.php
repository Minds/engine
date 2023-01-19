<?php
declare(strict_types=1);

namespace Minds\Core\Verification\Helpers;

use Imagick;
use ImagickException;
use Zend\Diactoros\Stream;

class ImageProcessor
{
    private Stream $stream;

    public function __construct(
        private ?Imagick $imagick = null
    ) {
        $this->imagick ??= new Imagick();
    }

    public function withStream(Stream $stream): self
    {
        $instance = clone $this;

        $instance->setStream($stream);
        return $instance;
    }

    public function setStream(Stream $stream): void
    {
        $this->stream = $stream;
    }

    /**
     * @return void
     * @throws ImagickException
     */
    public function cropVerificationImage(): void
    {
        $this->imagick
            ->readImageFile($this->stream->detach());

        $imageWidth = $this->imagick->getImageWidth();
        $boxWidth = $imageWidth * 0.65;
        $boxWidth += $boxWidth * 0.1;

        $imageHeight = $this->imagick->getImageHeight();
        $boxHeight = $boxWidth / 3;
        if ($imageHeight < $boxHeight) {
            $boxHeight = $imageHeight;
        }

        $x = $imageWidth / 2 - $boxWidth / 2;
        $y = $imageHeight / 2 - $boxHeight / 2;

        $this->imagick->cropImage((int) $boxWidth, (int) $boxHeight, (int) $x, (int) $y);
        $this->imagick->setImageType(Imagick::IMGTYPE_GRAYSCALEMATTE);
    }

    /**
     * @return string
     * @throws ImagickException
     */
    public function getImageAsString(): string
    {
        return $this->imagick->getImageBlob();
    }
}
