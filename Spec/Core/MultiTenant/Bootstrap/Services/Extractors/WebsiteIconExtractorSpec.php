<?php

namespace Spec\Minds\Core\MultiTenant\Bootstrap\Services\Extractors;

use Minds\Core\MultiTenant\Bootstrap\Services\Extractors\WebsiteIconExtractor;
use Minds\Core\MultiTenant\Bootstrap\Clients\GoogleFaviconClient;
use Minds\Core\Log\Logger;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class WebsiteIconExtractorSpec extends ObjectBehavior
{
    private $googleFaviconClientMock;
    private $loggerMock;

    public function let(GoogleFaviconClient $googleFaviconClient, Logger $logger)
    {
        $this->googleFaviconClientMock = $googleFaviconClient;
        $this->loggerMock = $logger;

        $this->beConstructedWith($googleFaviconClient, $logger);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(WebsiteIconExtractor::class);
    }

    public function it_should_extract_icon()
    {
        $url = 'https://example.minds.com';
        $size = 128;
        $iconBlob = 'icon-blob';

        $this->googleFaviconClientMock->get($url, $size)->willReturn($iconBlob);

        $this->extract($url, $size)->shouldReturn($iconBlob);
    }

    public function it_should_return_null_if_extraction_fails()
    {
        $url = 'https://example.minds.com';
        $size = 128;
        $errorMessage = 'Error extracting icon';

        $this->googleFaviconClientMock->get($url, $size)->willThrow(new \Exception($errorMessage));
        
        $this->loggerMock->error(Argument::type(\Exception::class))->shouldBeCalled();

        $this->extract($url, $size)->shouldReturn(null);
    }

    public function it_should_use_default_size_if_size_is_not_provided()
    {
        $url = 'https://example.minds.com';
        $iconBlob = 'icon-blob';

        $this->googleFaviconClientMock->get($url, 32)->willReturn($iconBlob);

        $this->extract($url)->shouldReturn($iconBlob);
    }
}
