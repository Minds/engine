<?php
declare(strict_types=1);

namespace Minds\Core\Expo\Clients;

use \GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ClientException;
use Minds\Core\Expo\ExpoConfig;
use Minds\Core\Log\Logger;
use Minds\Exceptions\ServerErrorException;

/**
 * Client for interacting with Expo's HTTP API.
 */
class ExpoHttpClient
{
    /** Path for V2 projects endpoint */
    public const V2_PROJECTS_PATH = '/v2/projects';

    public function __construct(
        private GuzzleClient $guzzleClient,
        private ExpoConfig $expoConfig,
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
            $response = $this->guzzleClient->request($method, $this->expoConfig->httpApiBaseUrl . $path, [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => "Bearer {$this->expoConfig->bearerToken}"
                ],
                'body' => json_encode($body)
            ]);
        } catch(ClientException $e) {
            $this->logger->error($e);
            $response = json_decode($e->getResponse()->getBody()->getContents(), true) ?? null;
            throw new ServerErrorException($response['errors'][0]['message'] ?? 'An error occurred when calling the Expo API');
        }

        $response = json_decode($response->getBody()->getContents(), true);
        return $response['data'] ?? null;
    }
}
