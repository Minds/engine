<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Bootstrap\Services\Extractors;

use GuzzleHttp\Client as GuzzleClient;
use Minds\Core\Feeds\Activity\RichEmbed\Metascraper\Service as MetascraperService;
use Minds\Core\Log\Logger;
use Minds\Core\MultiTenant\Bootstrap\Models\ExtractedMetadata;

/**
 * Extracts metadata from a website.
 */
class MetadataExtractor
{
    public function __construct(
        private MetascraperService $metascraperService,
        private GuzzleClient $guzzleClient,
        private Logger $logger
    ) {
    }

    /**
     * Extracts metadata from a website.
     * @param string $siteUrl - The URL of the website to extract thumbnail URL from.
     * @return ExtractedMetadata|null - The extracted thumbnail URL or null if not found.
     */
    public function extractThumbnailUrl(string $siteUrl): ?string
    {
        try {
            $data = $this->getMetadata($siteUrl);
            return $data['links']['thumbnail'][0]['href'] ?? null;
        } catch (\Exception $e) {
            $this->logger->error($e);
            return null;
        }
    }

    /**
     * Extracts metadata from a website.
     * @param string $siteUrl - The URL of the website to extract metadata from.
     * @return ExtractedMetadata|null - The extracted metadata or null if not found.
     */
    public function extract(string $siteUrl): ?ExtractedMetadata
    {
        try {
            $data = $this->getMetadata($siteUrl);

            $logoUrl = $data['links']['thumbnail'][0]['href'] ?? null;
            
            if (!$logoUrl) {
                throw new \Exception("Logo URL not found");
            }
 
            $logoData = $this->guzzleClient->get($logoUrl)->getBody()->getContents();
            
            $this->logger->info("Metadata extracted: " . json_encode($data));

            return new ExtractedMetadata(
                logoUrl: $logoUrl,
                description: $data['meta']['description'] ?? '',
                logoData: $logoData ?? null,
                publisher: $data['publisher'] ?? null
            );
        } catch (\Exception $e) {
            $this->logger->error($e);
            return null;
        }
    }

    /**
     * Get Metadata from metascraper.
     * @param string $url - url to get metadata for.
     * @return array - metadata.
     */
    private function getMetadata(string $siteUrl): array
    {
        return $this->metascraperService->scrape($siteUrl);
    }
}
