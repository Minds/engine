<?php
declare(strict_types=1);

namespace Minds\Core\Payments\Lago\Clients;

use Exception;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Uri;

class PlansClient extends ApiClient
{
    public function __construct(
        HttpClient $httpClient
    ) {
        parent::__construct($httpClient);
    }

    /**
     * @return void
     * @throws GuzzleException
     * @throws Exception
     */
    public function getAllPlans(): void
    {
        $response = $this->httpClient->get(
            uri: Uri::withQueryValues(
                new Uri('/api/v1/plans'),
                keyValueArray: [
                    'page' => 1,
                    'per_page' => 12,
                ]
            )
        );

        if ($response->getStatusCode() !== 200) {
            throw new \Exception("Failed to create subscription");
        }

        $payload = json_decode($response->getBody()->getContents());
    }
}
