<?php

namespace Minds\Core\Webfinger;

use Minds\Exceptions\ServerErrorException;

class WebfingerService
{
    public function __construct(protected Client $client)
    {
    }

    /**
     * Fetches a webfinger resources from a uri (eg. acct:mark@minds.com).
     */
    public function get(string $uri): array
    {
        // Split the username
        [$_, $domain] = explode('@', $uri);
        $requestUrl = "https://$domain/.well-known/webfinger?resource=$uri";
        $response = $this->client->request('GET', $requestUrl);

        $json = json_decode($response->getBody()->getContents(), true);

        if (!is_array($json)) {
            throw new ServerErrorException('bad webfinger response');
        }

        return $json;
    }
}
