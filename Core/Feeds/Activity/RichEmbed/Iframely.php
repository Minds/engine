<?php
namespace Minds\Core\Feeds\Activity\RichEmbed;

use Minds\Core\Config\Config;
use GuzzleHttp;
use Psr\Http\Message\ResponseInterface;

class Iframely
{
    /**
     * @param GuzzleHttp\Client $httpClient
     * @param Config $config
     */
    public function __construct(protected GuzzleHttp\Client $httpClient, protected Config $config)
    {
    }
    
    /**
     * @param string $method
     * @param string $endpoint
     * @param array $body
     * @return ResponseInterface
     */
    public function request(string $method, string $endpoint, array $body = null): ResponseInterface
    {
        $endpoint = "{$this->getUriPrefix()}$endpoint";

        $json = $this->httpClient->request($method, $endpoint, [
                    'headers' => [
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
        return "https://open.iframe.ly/api/iframely";
    }
}
