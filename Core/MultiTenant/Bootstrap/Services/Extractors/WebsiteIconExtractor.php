<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Bootstrap\Services\Extractors;

use Minds\Core\Log\Logger;
use Minds\Core\MultiTenant\Bootstrap\Clients\GoogleFaviconClient;

/**
 * Extracts the icon from a website.
 */
class WebsiteIconExtractor
{
    public function __construct(
        private GoogleFaviconClient $googleFaviconClient,
        private Logger $logger
    ) {
    }

    /**
     * Extracts the icon from a website.
     * @param string $url - The URL of the website to extract the icon from.
     * @param int $size - The size of the icon to extract.
     * @return string|null - The URL of the icon or null if not found.
     */
    public function extract(string $url, int $size = 32): ?string
    {
        try {
            return $this->googleFaviconClient->get($url, $size);
        } catch (\Exception $e) {
            $this->logger->error($e);
            return null;
        }
    }
}
