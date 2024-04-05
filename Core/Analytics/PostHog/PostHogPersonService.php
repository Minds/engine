<?php
namespace Minds\Core\Analytics\PostHog;

use Minds\Core\Analytics\PostHog\Models\PostHogPerson;
use GuzzleHttp\Client as HttpClient;
use Minds\Exceptions\NotFoundException;

class PostHogPersonService
{
    public function __construct(
        private PostHogConfig $postHogConfig,
        private HttpClient $httpClient
    ) {
    }

    /**
     * Returns a PostHogPerson model if exist, if it doesn't a 404 exception is thrown
     */
    public function getPerson(int $guid): PostHogPerson
    {
        try {
            $response = $this->httpClient->get(
                uri: "api/projects/{$this->postHogConfig->getProjectId()}/persons",
                options: [
                    'query' => [
                        'distinct_id' => $guid,
                    ]
                ]
            );

            $data = json_decode($response->getBody()->getContents(), true);

            if (!$data['results']) {
                throw new NotFoundException();
            }

            return new PostHogPerson(
                id: $data['results'][0]['id'],
            );
        } catch (\Exception $e) {
            throw new NotFoundException();
        }

        return new PostHogPerson(
            id: $data['results'][0]['id'],
        );
    }

    /**
     * Deletes a Person on PostHog and removes their data
     */
    public function deletePerson(int $guid): bool
    {
        $person = $this->getPerson($guid);

        try {
            $response = $this->httpClient->delete(
                uri: "api/projects/{$this->postHogConfig->getProjectId()}/persons/{$person->id}",
            );

            return $response->getStatusCode() === 204;
        } catch (\Exception $e) {
            throw new NotFoundException();
        }
    }

}
