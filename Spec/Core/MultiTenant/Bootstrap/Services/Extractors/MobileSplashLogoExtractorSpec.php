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

    public function it_should_return_null_if_extraction_fails()
    {
        $squareLogoBlob = 'invalid-square-logo-blob';

        $this->logoImageProcessorMock->toPng(Argument::any())->willThrow(new \ImagickException('error'));

        $this->extract($squareLogoBlob)->shouldReturn(null);
    }
}
