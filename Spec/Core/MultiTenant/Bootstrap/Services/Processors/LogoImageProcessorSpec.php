<?php
declare(strict_types=1);

namespace Spec\Minds\Core\MultiTenant\Bootstrap\Services\Processors;

use Minds\Core\MultiTenant\Bootstrap\Services\Processors\LogoImageProcessor;
use PhpSpec\ObjectBehavior;
use Imagick;
use ImagickPixel;

class LogoImageProcessorSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(LogoImageProcessor::class);
    }

    public function it_should_convert_jpeg_image_to_png()
    {
        $image = new Imagick();
        $image->newImage(100, 100, new ImagickPixel('white'));
        $image->setImageFormat('jpeg');

        $result = $this->toPng($image);

        $result->getImageFormat()->shouldBe('PNG');
    }

    public function it_should_trim_image()
    {
        $image = new Imagick();
        $image->newImage(100, 100, new ImagickPixel('white'));
        $image->setImageFormat('png');

        $result = $this->trim($image);

        $result->getImagePage()['x']->shouldBe(0);
        $result->getImagePage()['y']->shouldBe(0);
    }

    public function it_should_add_padding_to_image()
    {
        $image = new Imagick();
        $image->newImage(100, 50, new ImagickPixel('white'));
        $image->setImageFormat('png');

        $result = $this->addPadding($image, 1.0);

        $result->getImageWidth()->shouldBe(100);
        $result->getImageHeight()->shouldBe(100);
    }
}
