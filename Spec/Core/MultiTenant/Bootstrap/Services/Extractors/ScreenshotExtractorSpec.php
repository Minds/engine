<?php

namespace Spec\Minds\Core\MultiTenant\Bootstrap\Services\Extractors;

use Minds\Core\MultiTenant\Bootstrap\Services\Extractors\ScreenshotExtractor;
use Minds\Core\MultiTenant\Bootstrap\Clients\ScreenshotOneClient;
use Minds\Core\Log\Logger;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ScreenshotExtractorSpec extends ObjectBehavior
{
    private $screenshotOneClientMock;
    private $loggerMock;

    public function let(ScreenshotOneClient $screenshotOneClient, Logger $logger)
    {
        $this->screenshotOneClientMock = $screenshotOneClient;
        $this->loggerMock = $logger;

        $this->beConstructedWith($screenshotOneClient, $logger);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(ScreenshotExtractor::class);
    }

    public function it_should_extract_screenshot()
    {
        $siteUrl = 'https://example.minds.com';
        $screenshotBlob = 'screenshot-blob';

        $this->screenshotOneClientMock->get($siteUrl)->willReturn($screenshotBlob);

        $this->extract($siteUrl)->shouldReturn($screenshotBlob);
    }

    public function it_should_return_null_if_extraction_fails()
    {
        $siteUrl = 'https://example.minds.com';
        $errorMessage = 'Error';

        $this->screenshotOneClientMock->get($siteUrl)->willThrow(new \Exception($errorMessage));
        
        $this->loggerMock->error(Argument::containingString($errorMessage))->shouldBeCalled();

        $this->extract($siteUrl)->shouldReturn(null);
    }
}
