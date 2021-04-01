<?php
namespace Minds\Core\Matrix;

use GuzzleHttp;
use Psr\Http\Message\ResponseInterface;

class Client
{
    /** @var GuzzleHttp\Client */
    protected $httpClient;

    /** @var Config */
    protected $matrixConfig;

    /** @var string */
    protected $accessToken;

    /**
     * @param GuzzleHttp\Client $httpClient
     * @param Config $config
     */
    public function __construct($httpClient = null, MatrixConfig $matrixConfig = null)
    {
        $this->httpClient = $httpClient ?? new GuzzleHttp\Client();
        $this->matrixConfig = $matrixConfig ?? new MatrixConfig();
        $this->accessToken = $this->matrixConfig->getAdminAccessToken();
    }

    /**
     * Use this when you are acting on the users behalf
     * @param string $accessToken
     * @return Client
     */
    public function setAccessToken(string $accessToken): Client
    {
        $client = clone $this;
        $client->accessToken = $accessToken;
        return $client;
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
                        'Authorization' =>  'Bearer ' . $this->accessToken,
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
        return "https://{$this->matrixConfig->getHomeserverApiDomain()}";
    }
}
