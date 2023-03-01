<?php

declare(strict_types=1);

namespace Tests\Support\Helper;

// here you can define custom actions
// all public methods declared in helper class will be available in $I

use Codeception\Exception\ModuleException;
use Codeception\Module;
use Codeception\Module\REST;
use Codeception\Util\Fixtures;

/**
 *
 */
class Activities extends Module
{
    private const ACTIVITY_CREATION_METHOD = 'PUT';
    private const ACTIVITY_CREATION_ENDPOINT = 'v3/newsfeed/activity';

    private const ACTIVITY_VOTE_METHOD = "PUT";
    private const ACTIVITY_VOTE_ENDPOINT = "v1/thumbs";

    /**
     * @param array $activityDetails
     * @param bool $checkResponse
     * @return void
     * @throws ModuleException
     */
    public function createActivityWithDetails(array $activityDetails, bool $checkResponse = false): void
    {
        /**
         * @var REST $apiClient
         */
        $apiClient = $this->getModule("REST");

        $apiClient->haveHttpHeader('Content-Type', "application/json");
        $apiClient->send(
            self::ACTIVITY_CREATION_METHOD,
            self::ACTIVITY_CREATION_ENDPOINT,
            $activityDetails
        );

        if ($checkResponse) {
            $apiClient->seeResponseCodeIs(200);
            $activity = json_decode($apiClient->response);

            Fixtures::add('created_activity', $activity);
        }
    }

    public function voteLastCreatedActivity(string $direction, array $clientMeta = []): void
    {
        $apiClient = $this->getModule("REST");

        $activityDetails = Fixtures::get('created_activity');

        if (count($clientMeta)) {
            $clientMeta = ['client_meta' => $clientMeta];
        }

        $apiClient->haveHttpHeader('Content-Type', "application/json");
        $apiClient->send(
            self::ACTIVITY_VOTE_METHOD,
            self::ACTIVITY_VOTE_ENDPOINT . "/$activityDetails->guid/$direction",
            $clientMeta
        );
    }
}
