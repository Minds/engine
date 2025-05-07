<?php
namespace Minds\Core\Analytics\PostHog;

use Minds\Core\Config\Config;
use GuzzleHttp\Client as HttpClient;

class PostHogQueryService
{
    public function __construct(
        private PostHogConfig $postHogConfig,
        private HttpClient $httpClient,
        private Config $config,
    ) {
    }

    /**
     * Allows for querying to PostHog events
     */
    public function query(string $query = 'SELECT * FROM events'): array
    {
        $response = $this->httpClient->post("api/projects/{$this->postHogConfig->getProjectId()}/query", [
            'json' => [
                'query' => [
                    'kind' => 'HogQLQuery',
                    'query' => $query,
                ]
            ]
        ]);

        $json = json_decode($response->getBody()->getContents(), true);

        return $json;
    }

}
