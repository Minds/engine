<?php

namespace Spec\Minds\Core\MultiTenant\Bootstrap\Services\Extractors;

use Minds\Core\MultiTenant\Bootstrap\Services\Extractors\MetadataExtractor;
use Minds\Core\Feeds\Activity\RichEmbed\Metascraper\Service as MetascraperService;
use GuzzleHttp\Client as GuzzleClient;
use Minds\Core\Log\Logger;
use Minds\Core\MultiTenant\Bootstrap\Models\ExtractedMetadata;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class MetadataExtractorSpec extends ObjectBehavior
{
    private $metascraperServiceMock;
    private $guzzleClientMock;
    private $loggerMock;

    public function let(MetascraperService $metascraperService, GuzzleClient $guzzleClient, Logger $logger)
    {
        $this->metascraperServiceMock = $metascraperService;
        $this->guzzleClientMock = $guzzleClient;
        $this->loggerMock = $logger;

        $this->beConstructedWith($metascraperService, $guzzleClient, $logger);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(MetadataExtractor::class);
    }

    public function it_should_extract_thumbnail_url()
    {
        $siteUrl = 'https://example.minds.com';
        $thumbnailUrl = 'https://example.minds.com/thumbnail.jpg';

        $this->metascraperServiceMock->scrape($siteUrl)->willReturn([
            'links' => [
                'thumbnail' => [
                    ['href' => $thumbnailUrl]
                ]
            ]
        ]);

        $this->extractThumbnailUrl($siteUrl)->shouldReturn($thumbnailUrl);
    }

    public function it_should_return_null_if_thumbnail_url_not_found()
    {
        $siteUrl = 'https://example.minds.com';

        $this->metascraperServiceMock->scrape($siteUrl)->willReturn([
            'links' => []
        ]);

        $this->extractThumbnailUrl($siteUrl)->shouldReturn(null);
    }

    public function it_should_extract_metadata()
    {
        $siteUrl = 'https://example.minds.com';
        $metadata = [
            'links' => [
                'thumbnail' => [
                    ['href' => 'https://example.minds.com/logo.png']
                ]
            ],
            'meta' => [
                'description' => 'Description'
            ],
            'publisher' => 'Publisher'
        ];

        $this->metascraperServiceMock->scrape($siteUrl)->willReturn($metadata);

        $result = $this->extract($siteUrl);
        $result->shouldBeAnInstanceOf(ExtractedMetadata::class);
        $result->getLogoUrl()->shouldReturn('https://example.minds.com/logo.png');
        $result->getDescription()->shouldReturn('Description');
        $result->getPublisher()->shouldReturn('Publisher');
    }

    public function it_should_return_null_if_metadata_extraction_fails()
    {
        $siteUrl = 'https://example.minds.com';

        $this->metascraperServiceMock->scrape($siteUrl)->willThrow(new \Exception('Extraction failed'));
        $this->loggerMock->error(Argument::type(\Exception::class))->shouldBeCalled();

        $this->extract($siteUrl)->shouldReturn(null);
    }

    public function it_should_handle_missing_metadata_fields()
    {
        $siteUrl = 'https://example.minds.com';
        $metadata = [
            'links' => [],
            'meta' => [],
            'publisher' => null
        ];

        $this->metascraperServiceMock->scrape($siteUrl)->willReturn($metadata);

        $result = $this->extract($siteUrl);
        $result->shouldBeAnInstanceOf(ExtractedMetadata::class);
        $result->getLogoUrl()->shouldReturn(null);
        $result->getDescription()->shouldReturn('');
        $result->getPublisher()->shouldReturn(null);
    }
}
