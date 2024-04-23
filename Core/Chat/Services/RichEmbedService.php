<?php
declare(strict_types=1);

namespace Minds\Core\Chat\Services;

use DateTime;
use Minds\Core\Chat\Entities\ChatRichEmbed;
use Minds\Core\Feeds\Activity\RichEmbed\Metascraper\Service as MetascraperService;
use Minds\Core\Log\Logger;
use Minds\Exceptions\ServerErrorException;
use Twitter\Text\Extractor;

/**
 * Rich embed service for chat messages.
 */
class RichEmbedService
{
    public function __construct(
        private readonly MetascraperService $metascraperService,
        private readonly Logger $logger
    ) {
    }

    /**
     * Parse rich embed from text.
     * @param string $text - text to parse.
     * @return ChatRichEmbed|null - parsed rich embed or null if failed to parse,
     * or did not find a URL to parse.
     */
    public function parseFromText(
        string $text
    ): ?ChatRichEmbed {
        if (!($url = $this->parseUrlFromText($text))) {
            return null;
        }

        try {
            $metadata = $this->metascraperService->scrape($url);

            if (!$metadata) {
                throw new ServerErrorException('Failed to scrape metadata');
            }

            if (!$metadata['url'] || !$metadata['meta']['canonical_url'] || !(
                $metadata['meta']['description'] ||
                $metadata['meta']['title'] ||
                $metadata['links']['thumbnail'][0]['href']
            )) {
                throw new ServerErrorException('Failed to scrape complete metadata');
            }

            return new ChatRichEmbed(
                url: $metadata['url'],
                canonicalUrl: $metadata['meta']['canonical_url'],
                title: $metadata['meta']['title'],
                description: $metadata['meta']['description'],
                author: $metadata['meta']['author'],
                thumbnailSrc: $metadata['links']['thumbnail'][0]['href'],
                createdTimestamp: new DateTime('now'),
                updatedTimestamp: new DateTime('now')
            );
        } catch(ServerErrorException $e) {
            $this->logger->error($e);
            return null;
        }
    }

    /**
     * Parse URL from text.
     * @param string $text - text to parse.
     * @return string|null - parsed URL or null if not found.
     */
    private function parseUrlFromText(string $text): ?string
    {
        $urls = Extractor::create()->extractUrls($text);
        return $urls && count($urls) ? $urls[0] : null;
    }
}
