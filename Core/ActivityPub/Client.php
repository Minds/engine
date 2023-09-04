<?php
namespace Minds\Core\ActivityPub;

use DateTime;
use Minds\Core\Config\Config;
use GuzzleHttp;
use GuzzleHttp\Psr7\Request;
use HttpSignatures\Context;
use Minds\Core\Di\Di;
use Psr\Http\Message\ResponseInterface;

class Client
{
    /** @var string[] HttpSignature Private Key */
    private array $privateKeys;

    public function __construct(
        protected GuzzleHttp\Client $httpClient,
        protected Config $config
    ) {
    }
    
    public function withPrivateKeys(array $privateKeys): Client
    {
        $instance = clone $this;
        $instance->privateKeys = $privateKeys;
        return $instance;
    }

    /**
     * @param string $method
     * @param string $endpoint
     * @param array $body
     * @return ResponseInterface
     */
    public function request(string $method, string $url, array $body = []): ResponseInterface
    {
        $request = new Request(
            method: $method,
            uri: $url,
            headers: [
                'Date' => (new DateTime())->format('D, d M Y H:i:s \G\M\T'),
                'Content-Type' => 'application/activity+json',
                'Accept' => 'application/activity+json',
            ],
            body: json_encode($body),
        );

        if (isset($this->privateKeys)) {
            $context = new Context([
                'keys' => $this->privateKeys,
                'algorithm' => 'rsa-sha256',
                'headers' => ['(request-target)', 'Date', 'Accept'],
            ]);
            $request = $context->signer()->signWithDigest($request);
        }

        $opts = [];

        if (($httpProxy = $this->config->get('http_proxy'))) {
            $opts['proxy'] =  $httpProxy;
        }
    
        $json = $this->httpClient->send($request, $opts);
       
        return $json;
    }

}
