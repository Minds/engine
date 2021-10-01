<?php
namespace Minds\Core\Feeds\TwitterSync;

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
    public function request(string $method, string $endpoint, array $body = null): ResponseInterface
    {
        $endpoint = "{$this->getUriPrefix()}/$endpoint";

        $json = $this->httpClient->request($method, $endpoint, [
                    'headers' => [
                        'Authorization' => "Bearer " . $this->config->get('twitter')['bearer_token'],
                        'Content-Type' => 'application/json'
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
        return "https://api.twitter.com";
    }
}
