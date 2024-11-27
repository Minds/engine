<?php
declare(strict_types=1);

namespace Minds\Core\Media\MediaDownloader;

use GuzzleHttp\Client;
use Minds\Core\Log\Logger;

class AudioDownloader implements MediaDownloaderInterface
{
    /** @var int timeout for request in seconds */
    const REQUEST_TIMEOUT_SECONDS = 60;

    public function __construct(
        private readonly Client $client,
        private readonly Logger $logger,
    ) {
    }

    /**
     * Downloads audio from a URL.
     * @param string $url - The URL to download from
     * @return string|null - audio file contents.
     */
    public function download(string $url): ?string
    {
        try {
            $response = $this->client->get($url, [
                'timeout' => self::REQUEST_TIMEOUT_SECONDS
            ]);
            return $response?->getBody()?->getContents() ?? null;
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            throw $e;
        }
    }
}
