<?php
namespace Minds\Integrations\MemberSpace;

use GuzzleHttp\Client;
use Minds\Integrations\MemberSpace\Models\MemberSpaceProfile;

class MemberSpaceService
{
    public function __construct(
        private Client $httpClient,
    ) {
        
    }

    /**
     * Return the MemberSpace current membership profile
     */
    public function getProfile(string $accessToken): MemberSpaceProfile
    {
        $response = $this->httpClient->get(
            uri: "https://api.memberspace.com/v1/members/me",
            options: [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                ]
            ]
        );
        $body = json_decode($response->getBody()->getContents(), true);

        return new MemberSpaceProfile(
            id: $body['id'],
            email: $body['email'],
            name: $body['name']
        );
    }

}
