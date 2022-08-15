<?php

namespace Minds\Core\Feeds\Activity\RichEmbed\Metascraper;

use GuzzleHttp;
use GuzzleHttp\ClientInterface;
use Minds\Core\Feeds\Activity\RichEmbed\Metascraper\Cache\Manager as CacheManager;
use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Core\Log\Logger;
use Minds\Exceptions\ServerErrorException;

/**
 * Service for accessing Metascraper Server to parse rich-embeds.
 */
class Service
{
    /** @var int timeout for requests in seconds */
    private $requestTimeoutSeconds = 30;

    /**
     * Constructor.
     * @param ClientInterface|null $httpClient
     * @param Logger|null $logger
     * @param Config|null $config
     * @param CacheManager|null $cache
     */
    public function __construct(
        public ?ClientInterface $httpClient = null,
        public ?Logger $logger = null,
        public ?Config $config = null,
        public ?CacheManager $cacheManager = null
    ) {
        $this->httpClient ??= new GuzzleHttp\Client();
        $this->logger ??= Di::_()->get('Logger');
        $this->config ??= Di::_()->get('Config');
        $this->cacheManager ??= new CacheManager();

        if ($requestTimeoutSeconds = $this->config->get('metascraper')['request_timeout'] ?? false) {
            $this->requestTimeoutSeconds = $requestTimeoutSeconds;
        }
    }

    /**
     * Call service to scrape a given URL.
     * @param string $url - given URL to scrape.
     * @throws ServerErrorException - if no base url is set.
     * @throws GuzzleException - if there is an error in the request.
     * @return array - array of data returned by Metascraper server.
     */
    public function scrape(string $url): array
    {
        $endpoint = $this->getBaseUrl().'scrape';

        if (!$this->shouldBypassCache()) {
            try {
                $cachedMetadata = $this->cacheManager->getExported($url);

                if ($cachedMetadata) {
                    return $cachedMetadata;
                }
            } catch (\Exception $e) {
                // log and continue.
                $this->logger->error($e);
            }
        }
        try {
            $response = $this->httpClient->request('GET', $endpoint, [
                'headers' => [
                    'Content-Type' => 'application/json'
                ],
                'query' => [
                    'targetUrl' => $url
                ],
                'timeout' => $this->requestTimeoutSeconds
            ]);

            $responseData = json_decode($response->getBody()->getContents(), true);

            if ($responseData['status'] === 200 && $responseData['data']) {
                $metadata = (new Metadata())->fromMetascraperData($responseData['data']);
                $this->cacheManager->set($url, $metadata);
                return $metadata->export();
            }
        } catch (\Exception $e) {
            $this->logger->error($e);
        }

        throw new ServerErrorException(
            $responseData['message'] ??
            'Error occurred getting metadata for ' . $url
        );
    }

    /**
     * Call health-check endpoint, return true if healthy.
     * @throws GuzzleException - if there is an error in the request.
     * @return boolean - true if healthy.
     */
    public function isHealthy(): bool
    {
        $response = $this->httpClient->request('GET', $this->getBaseUrl(), [
            'headers' => ['Content-Type' => 'application/json'],
            'timeout' => $this->requestTimeoutSeconds
        ]);
        $responseData = json_decode($response->getBody()->getContents(), true);
        return $responseData['status'] && $responseData['status'] === 200;
    }

    /**
     * Gets base url for service.
     * @throws ServerErrorException - if no base url is set.
     * @return string base url for service.
     */
    private function getBaseUrl(): string
    {
        $baseUrl = $this->config->get('metascraper')['base_url'] ?? false;
        if (!$baseUrl) {
            throw new ServerErrorException('No Base URL is set for Metascraper service');
        }
        return $baseUrl;
    }

    /**
     * Whether cache should be bypassed.
     * @return boolean - true if cache should be bypassed.
     */
    private function shouldBypassCache(): bool
    {
        return $this->config->get('metascraper')['bypass_cache'] ?? false;
    }
}
