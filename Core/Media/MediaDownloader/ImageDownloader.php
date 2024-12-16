<?php
declare(strict_types=1);

namespace Minds\Core\Media\MediaDownloader;

use GuzzleHttp\Client;
use Minds\Core\Log\Logger;
use Psr\Http\Message\ResponseInterface;

class ImageDownloader implements MediaDownloaderInterface
{
    /** @var int timeout for request in seconds */
    const REQUEST_TIMEOUT_SECONDS = 20;

    public function __construct(
        private readonly Client $client,
        private readonly Logger $logger,
    ) {
    }

    /**
     * Downloads audio from a URL.
     * @param string $url - The URL to download from
     * @return ResponseInterface|null - audio file contents.
     */
    public function download(string $url): ?ResponseInterface
    {
        try {
            $response = $this->client->get($url, [
                'timeout' => self::REQUEST_TIMEOUT_SECONDS,
                'headers' => [
                    'Accept' => 'image/*',
                ]
            ]);

            $contentType = $response->getHeader('Content-Type')[0] ?? '';
            if (!str_starts_with($contentType, 'image/')) {
                throw new \Exception("Invalid content type: $contentType");
            }

            return $response;
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            throw $e;
        }
    }
}
