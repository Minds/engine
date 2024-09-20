<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Bootstrap\Services\Extractors;

use Minds\Core\Log\Logger;
use Minds\Core\MultiTenant\Bootstrap\Clients\ScreenshotOneClient;

/**
 * Extracts a screenshot of a website.
 */
class ScreenshotExtractor
{
    public function __construct(
        private ScreenshotOneClient $screenshotOneClient,
        private Logger $logger
    ) {
    }

    /**
     * Fetches a screenshot of a website.
     * @param string $siteUrl - The URL of the website to fetch a screenshot of.
     * @return string - The URL of the screenshot.
     */
    public function extract(string $siteUrl): ?string
    {
        try {
            return $this->screenshotOneClient->get($siteUrl);
        } catch (\Exception $e) {
            $this->logger->error("Error extracting screenshot: " . $e->getMessage());
            return null;
        }
    }
}
