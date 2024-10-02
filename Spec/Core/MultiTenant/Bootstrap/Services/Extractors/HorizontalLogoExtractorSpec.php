<?php

namespace Spec\Minds\Core\MultiTenant\Bootstrap\Services\Extractors;

use Minds\Core\Config;
use Minds\Core\Log\Logger;
use Minds\Core\MultiTenant\Bootstrap\Services\Extractors\HorizontalLogoExtractor;
use Minds\Core\MultiTenant\Bootstrap\Services\Processors\LogoImageProcessor;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class HorizontalLogoExtractorSpec extends ObjectBehavior
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
        $this->shouldHaveType(HorizontalLogoExtractor::class);
    }

    public function it_should_return_null_if_extraction_fails()
    {
        $squareLogoBlob = 'invalid-square-logo-blob';

        $image = new \Imagick();

        $this->logoImageProcessorMock->toPng($image)->willThrow(new \ImagickException());

        $this->extract($squareLogoBlob)->shouldReturn(null);
    }
}
