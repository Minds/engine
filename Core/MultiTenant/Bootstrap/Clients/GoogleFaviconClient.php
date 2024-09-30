<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Bootstrap\Clients;

use GuzzleHttp\Client as GuzzleClient;
use Minds\Core\Config\Config;

/**
 * Client for Google's Favicon endpoint. Fetches the favicon for a given site.
 */
class GoogleFaviconClient
{
    /** @var int Request timeout. */
    const TIMEOUT = 30;

    public function __construct(
        private GuzzleClient $guzzleClient,
        private Config $config
    ) {
    }

    /**
     * Get the favicon for a given site.
     * @param string $url - The URL of the site.
     * @param integer $size - The size of the favicon in pixels.
     * @return string - The favicon blob.
     */
    public function get(string $url, int $size = 32): ?string
    {
        $domain = parse_url($url, PHP_URL_HOST);
        $response = $this->guzzleClient->get(
            "https://www.google.com/s2/favicons?domain=$domain&sz=$size",
            [ 'timeout' => self::TIMEOUT ]
        );
        return $response?->getBody()?->getContents();
    }
}
