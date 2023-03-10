<?php
namespace Minds\Core\Email\Mautic;

use GuzzleHttp;
use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Psr\Http\Message\ResponseInterface;

class Client
{
    protected string $username;
    protected string $password;

    /**
     * @param GuzzleHttp\Client $httpClient
     * @param Config $config
     */
    public function __construct(
        protected ?GuzzleHttp\Client $httpClient = null,
        protected ?Config $config = null
    ) {
        $this->httpClient ??= new GuzzleHttp\Client();
        $this->config ??= Di::_()->get('Config');

        $this->username = $this->config->get('email')['mautic']['username'];
        $this->password = $this->config->get('email')['mautic']['password'];
    }

    /**
     * @param string $method
     * @param string $endpoint
     * @param array $body
     * @return ResponseInterface
     */
    public function request(string $method, string $endpoint, array $options = []): ResponseInterface
    {
        $endpoint = "{$this->getUriPrefix()}/$endpoint";

        $options = array_merge([
            'headers' => [
                'Authorization' =>  $this->getAuthorizationHeader(),
            ],
        ], $options);
    
        $json = $this->httpClient->request($method, $endpoint, $options);

        return $json;
    }

    /**
     * @return string
     */
    protected function getAuthorizationHeader(): string
    {
        $value = $this->username . ':' . $this->password;
        return 'Basic ' . base64_encode($value);
    }
    

    /**
     * @return string
     */
    protected function getUriPrefix(): string
    {
        return $this->config->get('email')['mautic']['base_url'];
    }
}
