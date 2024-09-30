<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Bootstrap\Clients;

use GuzzleHttp\Client as GuzzleClient;
use Minds\Core\Config\Config;

/**
 * Client for ScreenshotOne's API. Fetches screenshots for a given site.
 */
class ScreenshotOneClient
{
    /** @var int Request timeout. */
    const TIMEOUT = 30;

    public function __construct(
        private GuzzleClient $guzzleClient,
        private Config $config,
    ) {
    }

    /**
     * Get the screenshot for a given site.
     * @param string $siteUrl - The URL of the site.
     * @param integer $viewportWidth - The width of the viewport in pixels.
     * @param integer $viewportHeight - The height of the viewport in pixels.
     * @return string - The screenshot blob.
     */
    public function get(string $siteUrl, int $viewportWidth = 1920, int $viewportHeight = 1080): ?string
    {
        $queryString = http_build_query($this->getDefaultOptions($siteUrl, $viewportWidth, $viewportHeight));

        $response = $this->guzzleClient->request(
            'GET',
            $this->getBaseUrl() . 'take?' . $queryString,
            [ 'timeout' => self::TIMEOUT ]
        );

        return $response->getBody()->getContents();
    }

    /**
     * Get the default options for the screenshot request.
     * @param string $siteUrl - The URL of the site.
     * @param integer $viewportWidth - The width of the viewport in pixels.
     * @param integer $viewportHeight - The height of the viewport in pixels.
     * @return array - The default options.
     */
    private function getDefaultOptions($siteUrl, $viewportWidth = 1920, $viewportHeight = 1080): array
    {
        return [
            'access_key' => $this->getApiKey(),
            'url' => $siteUrl,
            'full_page' => 'true',
            'full_page_scroll' => 'false',
            'viewport_width' => $viewportWidth,
            'viewport_height' => $viewportHeight,
            'device_scale_factor' => 1,
            'format' => 'jpg',
            'image_quality' => 80,
            'block_ads' => 'true',
            'block_cookie_banners' => 'true',
            'block_banners_by_heuristics' => 'false',
            'block_trackers' => 'true',
            'delay' => 0,
            'timeout' => self::TIMEOUT
        ];
    }

    /**
     * Get the API key.
     * @return string|null - The API key.
     */
    private function getApiKey(): ?string
    {
        return $this->config->get('screenshot_one')['api_key'] ?? null;
    }

    /**
     * Get the base URL.
     * @return string|null - The base URL.
     */
    private function getBaseUrl(): ?string
    {
        return $this->config->get('screenshot_one')['base_url'] ?? 'https://api.screenshotone.com/';
    }
}
