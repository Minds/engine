<?php

namespace Spec\Minds\Core\MultiTenant\Bootstrap\Services\Extractors;

use Minds\Core\Config;
use Minds\Core\Log\Logger;
use Minds\Core\MultiTenant\Bootstrap\Services\Extractors\MobileSplashLogoExtractor;
use Minds\Core\MultiTenant\Bootstrap\Services\Processors\LogoImageProcessor;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class MobileSplashLogoExtractorSpec extends ObjectBehavior
{
    private $logoImageProcessorMock;
    private $loggerMock;

    public function let(LogoImageProcessor $logoImageProcessor, Logger $logger)
    {
        $this->logoImageProcessorMock = $logoImageProcessor;
        $this->loggerMock = $logger;

        $this->beConstructedWith($logoImageProcessor, $logger);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(MobileSplashLogoExtractor::class);
    }

    public function it_should_extract_mobile_splash_logo_for_horizontal_image()
    {
        $squareLogoBlob = file_get_contents(Config::build()->path . 'engine/Assets/avatars/default-small.png');

        $image = new \Imagick();
        $image->readImageBlob($squareLogoBlob);
        $image->scaleImage(200, 100); // Make it horizontal

        $this->logoImageProcessorMock->toPng(Argument::type(\Imagick::class))->willReturn($image);
        $this->logoImageProcessorMock->addPadding(Argument::type(\Imagick::class), Argument::type('float'))->willReturn($image);

        $result = $this->extract($squareLogoBlob);
        $result->shouldBeString();
    }

    public function it_should_extract_mobile_splash_logo_for_vertical_image()
    {
        $squareLogoBlob = file_get_contents(Config::build()->path . 'engine/Assets/avatars/default-small.png');

        $image = new \Imagick();
        $image->readImageBlob($squareLogoBlob);
        $image->scaleImage(100, 200); // Make it vertical

        $this->logoImageProcessorMock->toPng(Argument::type(\Imagick::class))->willReturn($image);
        $this->logoImageProcessorMock->addPadding(Argument::type(\Imagick::class), 3.66)->willReturn($image);

        $result = $this->extract($squareLogoBlob);
        $result->shouldBeString();
    }

    public function it_should_return_null_if_extraction_fails()
    {
        $squareLogoBlob = 'invalid-square-logo-blob';

        $this->logoImageProcessorMock->toPng(Argument::any())->willThrow(new \ImagickException('error'));

        $this->extract($squareLogoBlob)->shouldReturn(null);
    }
}
