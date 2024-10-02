<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Bootstrap\Services\Extractors;

use Minds\Core\Log\Logger;
use Minds\Core\MultiTenant\Bootstrap\Clients\JinaClient;

/**
 * Extracts website's content expressed in markdown.
 */
class MarkdownExtractor
{
    public function __construct(
        private JinaClient $jinaClient,
        private Logger $logger
    ) {
    }

    /**
     * Extracts website's content expressed in markdown.
     * @param string $siteUrl - The URL of the website to extract markdown from.
     * @return string|null - The markdown content or null if not found.
     */
    public function extract(string $siteUrl): ?string
    {
        try {
            $data = $this->jinaClient->get($siteUrl);
            return $data['content'] ?? null;
        } catch (\Exception $e) {
            $this->logger->error('Error extracting markdown from site: ' . $siteUrl, ['error' => $e]);
            return null;
        }
    }
}
