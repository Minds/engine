<?php

namespace Spec\Minds\Core\MultiTenant\Bootstrap\Services\Extractors;

use Minds\Core\MultiTenant\Bootstrap\Services\Extractors\MarkdownExtractor;
use Minds\Core\MultiTenant\Bootstrap\Clients\JinaClient;
use Minds\Core\Log\Logger;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class MarkdownExtractorSpec extends ObjectBehavior
{
    private $jinaClientMock;
    private $loggerMock;

    public function let(JinaClient $jinaClient, Logger $logger)
    {
        $this->jinaClientMock = $jinaClient;
        $this->loggerMock = $logger;

        $this->beConstructedWith($jinaClient, $logger);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(MarkdownExtractor::class);
    }

    public function it_should_extract_markdown_from_site()
    {
        $siteUrl = 'https://example.minds.com';
        $markdownContent = '# Title \n markdown.';

        $this->jinaClientMock->get($siteUrl)->willReturn(['content' => $markdownContent]);

        $this->extract($siteUrl)->shouldReturn($markdownContent);
    }

    public function it_should_return_null_if_no_content_found()
    {
        $siteUrl = 'https://example.minds.com';

        $this->jinaClientMock->get($siteUrl)->willReturn([]);

        $this->extract($siteUrl)->shouldReturn(null);
    }

    public function it_should_log_error_if_extraction_fails()
    {
        $siteUrl = 'https://example.minds.com';
        $exceptionMessage = 'Error extracting markdown';

        $this->jinaClientMock->get($siteUrl)->willThrow(new \Exception($exceptionMessage));
        $this->loggerMock->error('Error extracting markdown from site: ' . $siteUrl, Argument::any())->shouldBeCalled();

        $this->extract($siteUrl)->shouldReturn(null);
    }
}
