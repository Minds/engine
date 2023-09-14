<?php
namespace Minds\Core\Webfinger;

class Manager
{
    public function __construct(protected Client $client)
    {
        
    }

    /**
     * Fetches a webfinger resources from a uri (eg. acct:mark@minds.com)
     */
    public function get(string $uri): array
    {
        // Split the username
        [$_, $domain] = explode('@', $uri);
        $requestUrl = "https://$domain/.well-known/webfinger?resource=$uri";
        $response = $this->client->request('GET', $requestUrl);

        $json = json_decode($response->getBody()->getContents(), true);

        return $json;
    }
}
