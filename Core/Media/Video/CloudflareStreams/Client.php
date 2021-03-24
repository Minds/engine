<?php
namespace Minds\Core\Media\Video\CloudflareStreams;

use Minds\Core\Config;
use GuzzleHttp;
use Minds\Core\Di\Di;
use Psr\Http\Message\ResponseInterface;

class Client
{
    /** @var GuzzleHttp\Client */
    protected $httpClient;

    /** @var Config */
    protected $config;

    /**
     * @param GuzzleHttp\Client $httpClient
     * @param Config $config
     */
    public function __construct($httpClient = null, $config = null)
    {
        $this->httpClient = $httpClient ?? new GuzzleHttp\Client();
        $this->config = $config ?? Di::_()->get('Config');
    }
    
    /**
     * @param string $method
     * @param string $endpoint
     * @param array $body
     * @return ResponseInterface
     */
    public function request(string $method, string $endpoint, array $body = []): ResponseInterface
    {
        $endpoint = "{$this->getUriPrefix()}/$endpoint";

        $json = $this->httpClient->request($method, $endpoint, [
                    'headers' => [
                        'X-Auth-Key' => $this->config->get('cloudflare')['api_key'],
                        'X-Auth-Email' => $this->config->get('cloudflare')['email']
                    ],
                    'json' => $body
                ]);
       
        return $json;
    }
    
    /**
     * @return string
     */
    protected function getUriPrefix(): string
    {
        return "https://api.cloudflare.com/client/v4/accounts/" . $this->config->get('cloudflare')['account_id'];
    }
}
