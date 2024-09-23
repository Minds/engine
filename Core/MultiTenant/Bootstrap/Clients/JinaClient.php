<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Bootstrap\Clients;

use GuzzleHttp\Client as GuzzleClient;
use Minds\Core\Config\Config;
use Minds\Core\MultiTenant\Bootstrap\Enums\JinaReturnFormat;

/**
 * Client for Jina's API. Fetches screenshots and markdown representations of a given site.
 */
class JinaClient
{
    /** @var int Request timeout. */
    const TIMEOUT = 30;

    public function __construct(
        private GuzzleClient $guzzleClient,
        private Config $config,
    ) {
    }

    /**
     * Get the metadata for a given site.
     * @param string $siteUrl - The URL of the site.
     * @param JinaReturnFormat|null $returnFormat - The format of the return data.
     * @return array|null - The metadata for the site.
     */
    public function get(string $siteUrl, JinaReturnFormat $returnFormat = null): ?array
    {
        $url = $this->getBaseUrl().$siteUrl;

        $headers = [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $this->getApiKey()
        ];

        if ($returnFormat) {
            $headers['X-Return-Format'] = $returnFormat->value;
        }

        $response = $this->guzzleClient->get($url, [
            'headers' => $headers,
            'timeout' => self::TIMEOUT
        ]);

        $data = json_decode($response?->getBody()?->getContents(), true);
        return $data['data'] ?? null;
    }

    /**
     * Get the API key.
     * @return string|null - The API key.
     */
    private function getApiKey(): ?string
    {
        return $this->config->get('jina')['api_key'] ?? null;
    }

    /**
     * Get the base URL.
     * @return string|null - The base URL.
     */

    private function getBaseUrl(): ?string
    {
        return $this->config->get('jina')['base_url'] ?? 'https://r.jina.ai';
    }
}
