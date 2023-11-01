<?php
declare(strict_types=1);

namespace Minds\Core\Expo\Clients;

use \GuzzleHttp\Client as GuzzleClient;
use Minds\Core\Expo\ExpoConfig;
use Minds\Core\Log\Logger;
use Minds\Exceptions\ServerErrorException;

/**
 * Client for interacting with Expo's HTTP API.
 */
class ExpoHttpClient
{
    public function __construct(
        private GuzzleClient $guzzleClient,
        private ExpoConfig $config,
        private Logger $logger
    ) {
    }

    /**
     * Make a request to the Expo API.
     * @param string $method - request method.
     * @param string $method - API path.
     * @param array|null $body - body to send with the request.
     * @throws ServerErrorException - if the Expo API returns errors.
     * @return array|null - response or null on error during making request (no value returned).
     */
    public function request(string $method, string $path, array $body = null): ?array
    {
        try {
            $response = $this->guzzleClient->request($method, $this->config->httpApiBaseUrl . $path, [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => "Bearer {$this->config->bearerToken}"
                ],
                'body' => json_encode($body)
            ]);
        } catch(\Exception $e) {
            $this->logger->error($e);
            return null;
        }

        $response = json_decode($response->getBody()->getContents(), true);
        
        // if (isset($response['errors'])) {
        //     $error = $response['errors'][0];
        //     $errorMessagePrefix = '';
        //     if (isset($error['extensions'])) {
        //         $errorMessagePrefix = $error['extensions']['code'] . '; ' . $error['extensions']['errorCode'] . '; ';
        //     }
        //     $this->logger->error(json_encode($error));
        //     throw new ServerErrorException($errorMessagePrefix . $response['errors'][0]['message']);
        // }

        return $response;
    }
}
